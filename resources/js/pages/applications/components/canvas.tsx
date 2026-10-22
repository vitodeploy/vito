import { useEffect, useMemo, useRef } from 'react';
import { router } from '@inertiajs/react';
import { Background, BackgroundVariant, Controls, Edge, Node, NodeProps, ReactFlow, useReactFlow, useStore } from '@xyflow/react';
import { BoxIcon, DatabaseIcon, GlobeIcon, NetworkIcon, ServerIcon, ZapIcon } from 'lucide-react';
import FlowNode from '@/components/flow-node';
import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';
import { useConfigs } from '@/stores/bootstrap-store';
import { EnvironmentTopology, TopologyNode } from '@/types/application';
import Status from './status';
import '@xyflow/react/dist/style.css';

type CanvasNode = Node<{ node: TopologyNode; selected: boolean }, 'card'>;

export const panelProps = ['component', 'capabilities', 'domainOptions', 'componentDomains', 'componentScripts', 'inheritedScripts', 'workers', 'cronjobs', 'backendTargets', 'resource', 'can'];

const icons = { load_balancer: NetworkIcon, app: BoxIcon, external: ServerIcon, database: DatabaseIcon, redis: ZapIcon };
const kinds = { load_balancer: 'Load balancer', app: 'App', external: 'External backend', database: 'Database', redis: 'Redis / Valkey' };

function Card({ data: { node, selected } }: NodeProps<CanvasNode>) {
  const Icon = icons[node.type];
  const capabilities = useConfigs()?.application.capabilities;
  const kind = node.type === 'app' && node.capabilities.length
    ? node.capabilities.map((id) => capabilities?.find((capability) => capability.id === id)?.label ?? id).join(' · ')
    : kinds[node.type];

  return (
    <FlowNode
      connectable={false}
      className={cn('w-64 rounded-xl p-0 shadow-sm transition-colors [&_[data-handlepos]]:!opacity-0', selected && 'border-primary ring-primary/30 ring-2', node.needs_redeploy && !selected && 'border-warning/70 border-dashed')}
    >
      <button
        type="button"
        className="nodrag flex w-full cursor-pointer flex-col rounded-xl text-left focus-visible:outline-2 focus-visible:outline-ring"
        aria-label={`${node.name}, ${kind}, ${node.status}${node.needs_redeploy ? ', needs deploy' : ''}`}
        aria-pressed={selected}
      >
        <span className="flex items-center gap-3 p-3">
          <span className="bg-muted flex size-9 shrink-0 items-center justify-center rounded-lg"><Icon className="size-4" /></span>
          <span className="flex min-w-0 flex-1 flex-col">
            <span className="truncate text-sm font-medium" title={node.name}>{node.name}</span>
            <span className="text-muted-foreground truncate text-xs">{node.server_name ? `${kind} on ${node.server_name}` : kind}</span>
          </span>
          {node.needs_redeploy && <span className="bg-warning size-2 shrink-0 rounded-full" title="Needs deploy" />}
        </span>
        <span className="text-muted-foreground flex h-9 items-center justify-between gap-3 border-t px-3 text-xs">
          {node.type === 'external' ? <span>Not managed by Vito</span> : <Status status={node.status} color={node.status_color} className="shrink-0 text-xs" />}
          {node.domains.length > 0 && (
            <span className="flex min-w-0 items-center gap-1.5">
              <GlobeIcon className="size-3 shrink-0" />
              <span className="truncate">{node.domains[0]}</span>
              {node.domains.length > 1 && <span className="shrink-0">+{node.domains.length - 1}</span>}
            </span>
          )}
        </span>
      </button>
    </FlowNode>
  );
}

const nodeTypes = { card: Card };
const fitOptions = { maxZoom: 1, padding: 0.12 };

function FitOnResize({ count }: { count: number }) {
  const { fitView } = useReactFlow();
  const width = useStore((state) => state.width);
  const height = useStore((state) => state.height);
  const fitted = useRef(false);
  useEffect(() => {
    if (!width || !height) return;
    if (fitted.current) fitView({ ...fitOptions, duration: 200 });
    fitted.current = true;
  }, [fitView, width, height, count]);
  return null;
}

function layout(topology: EnvironmentTopology, selected?: string): CanvasNode[] {
  const columns = [
    topology.nodes.filter((node) => node.type === 'load_balancer'),
    topology.nodes.filter((node) => node.type === 'app' || node.type === 'external'),
    topology.nodes.filter((node) => node.type === 'database' || node.type === 'redis'),
  ].filter((column) => column.length > 0);
  const tallest = Math.max(...columns.map((column) => column.length));

  return columns.flatMap((column, x) => column.map((node, y) => ({
    id: node.id,
    type: 'card' as const,
    data: { node, selected: node.id === selected },
    position: { x: x * 310, y: ((tallest - column.length) / 2 + y) * 150 },
  })));
}

export default function Canvas({ topology, selected }: { topology: EnvironmentTopology; selected?: string }) {
  const { getActualAppearance } = useAppearance();
  const nodes = useMemo(() => layout(topology, selected), [topology, selected]);
  const edges = useMemo<Edge[]>(() => topology.edges.map((edge) => ({
    ...edge,
    type: 'smoothstep',
    animated: edge.target.startsWith('resource-'),
    className: edge.target.startsWith('resource-') ? 'opacity-70' : undefined,
  })), [topology.edges]);

  return (
    <ReactFlow<CanvasNode>
      nodes={nodes}
      edges={edges}
      nodeTypes={nodeTypes}
      nodesDraggable={false}
      nodesConnectable={false}
      nodesFocusable={false}
      elementsSelectable={false}
      edgesFocusable={false}
      deleteKeyCode={null}
      onNodeClick={(_, node) => router.visit(node.data.node.url, { preserveState: true, preserveScroll: true, only: panelProps })}
      fitView
      fitViewOptions={fitOptions}
      minZoom={0.3}
      proOptions={{ hideAttribution: true }}
      colorMode={getActualAppearance()}
    >
      <Background variant={BackgroundVariant.Dots} gap={20} />
      <Controls position="bottom-left" showInteractive={false} />
      <FitOnResize count={nodes.length} />
    </ReactFlow>
  );
}
