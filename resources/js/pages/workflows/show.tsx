import { Head, useForm, usePage } from '@inertiajs/react';
import Layout from '@/layouts/app/layout';
import { Workflow } from '@/types/workflow';
import { useAppearance } from '@/hooks/use-appearance';
import {
  ReactFlow,
  addEdge,
  applyNodeChanges,
  applyEdgeChanges,
  type Node,
  type Edge,
  type FitViewOptions,
  type OnNodesChange,
  type OnEdgesChange,
  type OnNodeDrag,
  type DefaultEdgeOptions,
  Position,
  Background,
  Controls,
  BackgroundVariant,
  MarkerType,
  Connection,
} from '@xyflow/react';
import '@xyflow/react/dist/style.css';
import { useCallback, useEffect, useState } from 'react';
import CustomNode from './components/custom-node';
import { toast } from 'sonner';
import { WorkflowAction } from '@/types/workflow-action';
import Actions from './components/actions';
import { Button } from '@/components/ui/button';
import { DotIcon, LoaderCircleIcon, SaveIcon, TrashIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import DeleteWorkflow from './components/delete-workflow';

type Page = {
  workflow: Workflow;
  actions: {
    [key: string]: WorkflowAction;
  };
};

const fitViewOptions: FitViewOptions = {
  padding: 0.2,
};

const defaultEdgeOptions: DefaultEdgeOptions = {
  animated: true,
  type: 'smoothstep',
  markerStart: MarkerType.Arrow,
  markerEnd: MarkerType.ArrowClosed,
};

const onNodeDrag: OnNodeDrag = (_, node) => {
  console.log('drag event', node.data);
};

export default function Show() {
  const page = usePage<Page>();
  const { getActualAppearance } = useAppearance();
  const [nodes, setNodes] = useState<Node[]>(page.props.workflow.nodes);
  const [edges, setEdges] = useState<Edge[]>(page.props.workflow.edges);

  useEffect(() => {
    form.setData('nodes', JSON.parse(JSON.stringify(nodes)));
    form.setData('edges', JSON.parse(JSON.stringify(edges)));
  }, [nodes, edges]);

  const form = useForm<{
    name: string;
    nodes: string;
    edges: string;
  }>({
    name: page.props.workflow.name,
    nodes: JSON.parse(JSON.stringify(page.props.workflow.nodes)),
    edges: JSON.parse(JSON.stringify(page.props.workflow.edges)),
  });

  const onNodesChange: OnNodesChange = useCallback((changes) => setNodes((nds) => applyNodeChanges(changes, nds)), [setNodes]);
  const onEdgesChange: OnEdgesChange = useCallback((changes) => setEdges((eds) => applyEdgeChanges(changes, eds)), [setEdges]);

  const wouldCreateLoop = (edges: Edge[], source: string, target: string): boolean => {
    if (source === target) return true; // direct self-loop

    const visited = new Set<string>();

    const dfs = (nodeId: string): boolean => {
      if (nodeId === source) return true; // found a cycle
      visited.add(nodeId);

      const outgoing = edges.filter((e) => e.source === nodeId);
      for (const e of outgoing) {
        if (!visited.has(e.target) && dfs(e.target)) {
          return true;
        }
      }
      return false;
    };

    return dfs(target);
  };

  const onConnect = useCallback(
    (connection: Connection) => {
      if (!connection.source || !connection.target) return;

      if (wouldCreateLoop(edges, connection.source, connection.target)) {
        toast.error('Connection would create a loop.');
        return;
      }

      const colors = {
        success: 'oklch(51.1% 0.262 276.966)',
        failure: '',
      };

      const outgoingEdges = edges.filter((edge) => edge.source === connection.source);
      const hasSuccess = outgoingEdges.some((e) => e.style?.stroke === colors.success);
      const hasFailure = outgoingEdges.some((e) => e.style?.stroke === colors.failure);

      if (outgoingEdges.length >= 2) {
        toast.error('This node already has 2 outgoing edges (1 success and 1 failure).');
        return;
      }

      let color: 'success' | 'failure';
      if (!hasSuccess) {
        color = 'success';
      } else if (!hasFailure) {
        color = 'failure';
      } else {
        toast.error('This node already has 2 outgoing edges (1 success and 1 failure).');
        return;
      }

      const newEdge: Edge = {
        ...connection,
        id: `${connection.source}-${connection.target}-${colors[color]}`,
        data: { status: color },
        style: { stroke: colors[color], strokeWidth: 2 },
        markerEnd: { type: 'arrowclosed', color: colors[color] },
      };

      setEdges((eds) => {
        const updated = addEdge(newEdge, eds);
        return [...updated].sort((a, b) => {
          if (a.style?.stroke === colors.success && b.style?.stroke === colors.failure) return 1;
          if (a.style?.stroke === colors.failure && b.style?.stroke === colors.success) return -1;
          return 0;
        });
      });
    },
    [edges, setEdges],
  );

  const onActionAdded = (action: WorkflowAction) => {
    const newNode: Node = {
      id: action.id,
      type: 'custom',
      sourcePosition: Position.Right,
      targetPosition: Position.Left,
      data: { label: action.label, action: action },
      position: { x: 0, y: 0 },
    };
    console.log('New node added:', newNode);
    // append node
    setNodes((nds) => nds.concat(newNode));
  };

  const saveWorkflow = () => {
    form.put(route('workflows.update', page.props.workflow.id));
  };

  return (
    <Layout>
      <Head title={`Workflow - ${page.props.workflow.name}`} />
      <div className="bg-accent relative h-full w-full border-none">
        <div className="bg-background absolute top-0 left-0 z-10 m-2 flex items-center justify-between gap-2 rounded-lg border p-3">
          <h2 className="text-lg font-semibold tracking-tight">{`Workflow - ${page.props.workflow.name}`}</h2>
          <DotIcon />
          <Button variant="ghost" className="size-7" onClick={saveWorkflow} disabled={form.processing}>
            {form.processing ? <LoaderCircleIcon className="animate-spin" /> : <SaveIcon />}
          </Button>
          <DeleteWorkflow workflow={page.props.workflow}>
            <Button variant="ghost" className="size-7">
              <TrashIcon />
            </Button>
          </DeleteWorkflow>
          <DotIcon />
          <Badge variant="default">Beta</Badge>
        </div>
        <Actions actions={page.props.actions} onActionAdded={onActionAdded} />
        <ReactFlow
          nodes={nodes}
          edges={edges}
          onNodesChange={onNodesChange}
          onEdgesChange={onEdgesChange}
          onConnect={onConnect}
          onNodeDrag={onNodeDrag}
          snapToGrid={true}
          snapGrid={[50, 50]}
          fitView
          fitViewOptions={fitViewOptions}
          defaultEdgeOptions={defaultEdgeOptions}
          colorMode={getActualAppearance()}
          nodeTypes={{
            custom: CustomNode,
          }}
        >
          <Background variant={BackgroundVariant.Dots} />
          <Controls position="bottom-right" />
        </ReactFlow>
      </div>
    </Layout>
  );
}
