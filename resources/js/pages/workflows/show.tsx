import { useCallback, useEffect, useMemo, useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import {
  addEdge,
  applyEdgeChanges,
  applyNodeChanges,
  Background,
  BackgroundVariant,
  type Connection,
  Controls,
  type DefaultEdgeOptions,
  type Edge,
  type FitViewOptions,
  MarkerType,
  type Node,
  type OnEdgesChange,
  type OnNodesChange,
  Position,
  ReactFlow,
  ReactFlowProvider,
  useReactFlow,
} from '@xyflow/react';
import '@xyflow/react/dist/style.css';
import { ClipboardPasteIcon, CopyPlusIcon, KeyboardIcon, LoaderCircleIcon, PlayIcon, SaveIcon, TrashIcon } from 'lucide-react';
import { toast } from 'sonner';
import Layout from '@/layouts/app/layout';
import { Separator } from '@/components/ui/separator';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useAppearance } from '@/hooks/use-appearance';
import { useDialog } from '@/hooks/use-dialog';
import { useDialogStore } from '@/stores/dialog-store';
import { useInputFocus } from '@/stores/useInputFocus';
import { useWorkflowClipboard } from '@/stores/workflow-clipboard';
import { Workflow } from '@/types/workflow';
import { WorkflowAction, WorkflowVariable } from '@/types/workflow-action';
import { WorkflowNodeData } from '@/types/workflow-node-data';
import CustomNode from './components/custom-node';
import Actions from './components/actions';
import ToolbarButton from './components/toolbar-button';
import { WorkflowEditor, WorkflowEditorContext } from './components/editor-context';

type WorkflowNode = Node<WorkflowNodeData>;

type Page = {
  workflow: Workflow;
  actions: Record<string, WorkflowAction>;
};

const fitViewOptions: FitViewOptions = { padding: 0.2 };

const defaultEdgeOptions: DefaultEdgeOptions = {
  animated: true,
  type: 'smoothstep',
  markerStart: MarkerType.Arrow,
  markerEnd: MarkerType.ArrowClosed,
};

const nodeTypes = { custom: CustomNode };

const edgeColors = { success: 'oklch(51.1% 0.262 276.966)', failure: '' };

const PASTE_OFFSET = 50;

const shortcuts = [
  ['⌘/Ctrl + S', 'Save'],
  ['⌘/Ctrl + C', 'Copy selected actions'],
  ['⌘/Ctrl + V', 'Paste actions'],
  ['⌘/Ctrl + D', 'Duplicate selected actions'],
  ['Backspace', 'Delete selection'],
  ['Shift + drag', 'Select multiple actions'],
  ['Double-click', 'Edit action'],
];

function wouldCreateLoop(edges: Edge[], source: string, target: string): boolean {
  const visited = new Set<string>();
  const reaches = (nodeId: string): boolean => {
    if (nodeId === source) return true;
    visited.add(nodeId);
    return edges.some((e) => e.source === nodeId && !visited.has(e.target) && reaches(e.target));
  };
  return reaches(target);
}

function edgesBetween(nodes: WorkflowNode[], edges: Edge[]): Edge[] {
  const ids = new Set(nodes.map((n) => n.id));
  return edges.filter((e) => ids.has(e.source) && ids.has(e.target));
}

function isEditableTarget(target: EventTarget | null): boolean {
  return target instanceof HTMLElement && (target.isContentEditable || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName));
}

function Editor() {
  const { workflow, actions } = usePage<Page>().props;
  const dialog = useDialog();
  const { getActualAppearance } = useAppearance();
  const { screenToFlowPosition, getNodes, getEdges } = useReactFlow<WorkflowNode>();
  const dialogActive = useDialogStore((state) => state.active !== null);
  const clipboardSize = useWorkflowClipboard((state) => (state.projectId === workflow.project_id ? state.nodes.length : 0));
  const [nodes, setNodes] = useState<WorkflowNode[]>(workflow.nodes as WorkflowNode[]);
  const [edges, setEdges] = useState<Edge[]>(workflow.edges);
  const [saving, setSaving] = useState(false);

  const onNodesChange: OnNodesChange<WorkflowNode> = useCallback((changes) => setNodes((nds) => applyNodeChanges(changes, nds)), []);
  const onEdgesChange: OnEdgesChange = useCallback((changes) => setEdges((eds) => applyEdgeChanges(changes, eds)), []);

  const actionFor = useCallback((handler: string) => Object.values(actions).find((action) => action.handler === handler), [actions]);

  const onConnect = useCallback(
    (connection: Connection) => {
      if (!connection.source || !connection.target) return;

      const edges = getEdges();
      if (connection.source === connection.target || wouldCreateLoop(edges, connection.source, connection.target)) {
        toast.error('Connection would create a loop.');
        return;
      }

      const outgoing = edges.filter((edge) => edge.source === connection.source);
      const statusOf = (e: Edge) => e.data?.status ?? 'success';
      const status = !outgoing.some((e) => statusOf(e) === 'success') ? 'success' : !outgoing.some((e) => statusOf(e) === 'failure') ? 'failure' : null;
      if (!status) {
        toast.error('This action already has 2 outgoing connections (1 success and 1 failure).');
        return;
      }

      const newEdge: Edge = {
        ...connection,
        id: `${connection.source}-${connection.target}-${status}`,
        data: { status },
        style: { stroke: edgeColors[status], strokeWidth: 2 },
        markerEnd: { type: MarkerType.ArrowClosed, color: edgeColors[status] },
      };
      setEdges((eds) => addEdge(newEdge, eds));
    },
    [getEdges],
  );

  const pasteNodes = useCallback(
    (source: WorkflowNode[], sourceEdges: Edge[]) => {
    const ids = new Map(source.map((n) => [n.id, crypto.randomUUID()]));
    const startId =
      getNodes().length === 0
        ? (source.find((n) => n.data.action.starting) ?? source.find((n) => !sourceEdges.some((e) => e.target === n.id)) ?? source[0])?.id
        : undefined;
    const pasted: WorkflowNode[] = source.map((n) => {
      const id = ids.get(n.id)!;
      return {
        ...structuredClone(n),
        id,
        selected: true,
        dragging: false,
        position: { x: n.position.x + PASTE_OFFSET, y: n.position.y + PASTE_OFFSET },
        data: { ...structuredClone(n.data), action: { ...structuredClone(n.data.action), id, starting: n.id === startId } },
      };
    });
    const pastedEdges: Edge[] = sourceEdges.map((e) => {
      const sourceId = ids.get(e.source)!;
      const targetId = ids.get(e.target)!;
      return { ...structuredClone(e), id: `${sourceId}-${targetId}-${e.data?.status ?? 'success'}`, source: sourceId, target: targetId, selected: false };
    });

    setNodes((nds) => [...nds.map((n) => ({ ...n, selected: false })), ...pasted]);
    setEdges((eds) => [...eds.map((e) => ({ ...e, selected: false })), ...pastedEdges]);

    return { pasted, pastedEdges };
    },
    [getNodes],
  );

  const pick = useCallback((ids?: string[]) => getNodes().filter((n) => (ids ? ids.includes(n.id) : n.selected)), [getNodes]);

  const copyNodes = useCallback(
    (ids?: string[]) => {
      const picked = pick(ids);
      if (picked.length === 0) return false;
      useWorkflowClipboard.getState().set(workflow.project_id, structuredClone(picked), structuredClone(edgesBetween(picked, getEdges())));
      toast.success(`Copied ${picked.length} action${picked.length > 1 ? 's' : ''}`);
      return true;
    },
    [pick, getEdges, workflow.project_id],
  );

  const paste = useCallback(() => {
    const clipboard = useWorkflowClipboard.getState();
    if (clipboard.projectId !== workflow.project_id || clipboard.nodes.length === 0) return false;
    const { pasted, pastedEdges } = pasteNodes(clipboard.nodes as WorkflowNode[], clipboard.edges);
    clipboard.set(workflow.project_id, structuredClone(pasted), structuredClone(pastedEdges));
    return true;
  }, [pasteNodes, workflow.project_id]);

  const duplicateNodes = useCallback(
    (ids?: string[]) => {
      const picked = pick(ids);
      if (picked.length === 0) return false;
      pasteNodes(picked, edgesBetween(picked, getEdges()));
      return true;
    },
    [pick, pasteNodes, getEdges],
  );

  const setStartingNode = useCallback(
    (id: string) =>
      setNodes((nds) => nds.map((n) => ({ ...n, data: { ...n.data, action: { ...n.data.action, starting: n.id === id } } }))),
    [],
  );

  const editNode = useCallback(
    (id: string) => {
      const nodes = getNodes();
      const node = nodes.find((n) => n.id === id);
      if (!node) return;
      const catalog = actionFor(node.data.action.handler);
      const variables = new Map<string, WorkflowVariable>();
      getEdges()
        .filter((e) => e.target === id)
        .forEach((e) => {
          const outputs = nodes.find((n) => n.id === e.source)?.data.action.outputs ?? {};
          Object.entries(outputs).forEach(([key, description]) => variables.set(key, { key, description }));
        });

      dialog.workflowActionForm.open({
        action: { ...node.data.action, fields: catalog?.fields ?? [], description: catalog?.description },
        variables: [...variables.values()],
        mode: 'edit',
        onSubmit: (action) => setNodes((nds) => nds.map((n) => (n.id === id ? { ...n, data: { ...n.data, label: action.label, action } } : n))),
      });
    },
    [getNodes, getEdges, actionFor, dialog],
  );

  const addAction = (action: WorkflowAction) => {
    dialog.workflowActionForm.open({
      action: { ...action, id: '', inputs: {} },
      mode: 'add',
      onSubmit: (added) => {
        const position = screenToFlowPosition({ x: window.innerWidth / 2, y: window.innerHeight / 2 });
        setNodes((nds) => [
          ...nds.map((n) => ({ ...n, selected: false })),
          {
            id: added.id,
            type: 'custom',
            sourcePosition: Position.Right,
            targetPosition: Position.Left,
            position,
            selected: true,
            data: { label: added.label, action: { ...added, starting: nds.length === 0 } },
          },
        ]);
      },
    });
  };

  const save = useCallback(() => {
    setSaving(true);
    router.put(`/workflows/${workflow.id}`, JSON.parse(JSON.stringify({ name: workflow.name, nodes: getNodes(), edges: getEdges() })), {
      preserveState: true,
      preserveScroll: true,
      onFinish: () => setSaving(false),
    });
  }, [workflow.id, workflow.name, getNodes, getEdges]);

  const run = () => {
    const starting = (workflow.nodes as WorkflowNode[]).find((n) => n.data.action?.starting);
    if (!starting) {
      toast.error('Save the workflow with a starting action before running it.');
      return;
    }
    dialog.workflowRun.open({
      workflowId: workflow.id,
      workflowName: workflow.name,
      inputs: workflow.run_inputs,
      fields: actionFor(starting.data.action.handler)?.fields,
    });
  };

  useEffect(() => {
    const onKeyDown = (e: KeyboardEvent) => {
      if (!(e.metaKey || e.ctrlKey) || e.altKey || isEditableTarget(e.target)) return;
      if (useDialogStore.getState().active || useInputFocus.getState().isFocused) return;

      if (e.code === 'KeyS') {
        e.preventDefault();
        if (!e.repeat) save();
      } else if (e.code === 'KeyC' && !window.getSelection()?.toString()) {
        if (copyNodes()) e.preventDefault();
      } else if (e.code === 'KeyV' && !e.repeat) {
        if (paste()) e.preventDefault();
      } else if (e.code === 'KeyD') {
        e.preventDefault();
        if (!e.repeat) duplicateNodes();
      }
    };

    document.addEventListener('keydown', onKeyDown);
    return () => document.removeEventListener('keydown', onKeyDown);
  }, [save, copyNodes, paste, duplicateNodes]);

  const editor = useMemo<WorkflowEditor>(
    () => ({ actionFor, editNode, copyNodes, duplicateNodes, setStartingNode }),
    [actionFor, editNode, copyNodes, duplicateNodes, setStartingNode],
  );

  return (
    <WorkflowEditorContext.Provider value={editor}>
      <Head title={`Workflow - ${workflow.name}`} />
      <div className="bg-accent relative h-full w-full border-none">
        <div className="bg-background absolute top-0 left-0 z-10 m-2 flex items-center gap-1 rounded-lg border p-2">
          <h2 className="px-1 text-lg font-semibold tracking-tight">{workflow.name}</h2>
          <Separator orientation="vertical" className="mx-1 data-[orientation=vertical]:h-5" />
          <ToolbarButton label="Save (⌘S)" onClick={save} disabled={saving}>
            {saving ? <LoaderCircleIcon className="animate-spin" /> : <SaveIcon />}
          </ToolbarButton>
          <ToolbarButton label="Run" onClick={run}>
            <PlayIcon />
          </ToolbarButton>
          <ToolbarButton label="Paste actions (⌘V)" onClick={() => paste()} disabled={clipboardSize === 0}>
            <ClipboardPasteIcon />
          </ToolbarButton>
          <ToolbarButton
            label="Duplicate workflow"
            onClick={() =>
              dialog.confirm.open({
                title: `Duplicate ${workflow.name}`,
                description: 'Creates a copy of the saved workflow. Unsaved changes are not included.',
                confirmLabel: 'Duplicate',
                method: 'post',
                url: `/workflows/${workflow.id}/duplicate`,
              })
            }
          >
            <CopyPlusIcon />
          </ToolbarButton>
          <ToolbarButton
            label="Delete workflow"
            className="hover:text-destructive"
            onClick={() =>
              dialog.confirm.open({
                title: `Delete ${workflow.name}`,
                description: 'Are you sure you want to delete this workflow?',
                variant: 'destructive',
                confirmLabel: 'Delete',
                method: 'delete',
                url: `/workflows/${workflow.id}`,
              })
            }
          >
            <TrashIcon />
          </ToolbarButton>
          <Tooltip>
            <TooltipTrigger asChild>
              <button type="button" className="text-muted-foreground hover:text-foreground flex size-7 items-center justify-center" aria-label="Keyboard shortcuts">
                <KeyboardIcon className="size-4" />
              </button>
            </TooltipTrigger>
            <TooltipContent side="bottom" className="flex flex-col gap-1">
              {shortcuts.map(([keys, description]) => (
                <div key={keys} className="flex justify-between gap-4">
                  <span className="font-mono">{keys}</span>
                  <span>{description}</span>
                </div>
              ))}
            </TooltipContent>
          </Tooltip>
        </div>
        <Actions actions={actions} onSelect={addAction} />
        <ReactFlow
          nodes={nodes}
          edges={edges}
          onNodesChange={onNodesChange}
          onEdgesChange={onEdgesChange}
          onConnect={onConnect}
          onNodeDoubleClick={(_, node) => editNode(node.id)}
          zoomOnDoubleClick={false}
          deleteKeyCode={dialogActive ? null : 'Backspace'}
          snapToGrid
          snapGrid={[50, 50]}
          fitView
          fitViewOptions={fitViewOptions}
          defaultEdgeOptions={defaultEdgeOptions}
          colorMode={getActualAppearance()}
          nodeTypes={nodeTypes}
        >
          <Background variant={BackgroundVariant.Dots} />
          <Controls position="bottom-right" />
        </ReactFlow>
      </div>
    </WorkflowEditorContext.Provider>
  );
}

export default function Show() {
  return (
    <Layout>
      <ReactFlowProvider>
        <Editor />
      </ReactFlowProvider>
    </Layout>
  );
}
