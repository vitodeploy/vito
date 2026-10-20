import { useMemo } from 'react';
import { router } from '@inertiajs/react';
import { Background, BackgroundVariant, Controls, MarkerType, Node, NodeProps, ReactFlow } from '@xyflow/react';
import { BoxesIcon, DatabaseIcon, GlobeIcon, NetworkIcon, ServerIcon } from 'lucide-react';
import FlowNode from '@/components/flow-node';
import { StatusRipple } from '@/components/status-ripple';
import { Badge } from '@/components/ui/badge';
import { useAppearance } from '@/hooks/use-appearance';
import { useConfigs } from '@/stores/bootstrap-store';
import { EnvironmentTopology, TopologyNode } from '@/types/application';
import '@xyflow/react/dist/style.css';

type TopologyFlowNode = Node<{ node: TopologyNode }, 'topology' | 'group'>;

const icons = { domain: GlobeIcon, load_balancer: NetworkIcon, app: BoxesIcon, external: ServerIcon, database: DatabaseIcon, redis: DatabaseIcon, server: ServerIcon };
const columns = { domain: 0, load_balancer: 1, app: 2, external: 2, database: 3, redis: 3, server: 1 };
const defaultEdgeOptions = { type: 'smoothstep', animated: false, markerEnd: { type: MarkerType.ArrowClosed } };

function TopologyCard({ data }: NodeProps<TopologyFlowNode>) {
  const { node } = data;
  const Icon = icons[node.type];
  const capabilities = useConfigs()?.application.capabilities;

  return (
    <FlowNode connectable={false} className="w-60 p-0">
      <button type="button" className="nodrag flex min-h-32 w-full flex-col gap-2 rounded-md p-3 text-left focus-visible:outline-2 focus-visible:outline-ring" aria-label={`Open ${node.name}, ${node.status}`}>
        <span className="flex w-full items-center gap-2"><Icon className="size-4 shrink-0" /><span className="truncate font-medium" title={node.name}>{node.name}</span></span>
        <span className="flex items-center gap-2 text-sm"><StatusRipple variant={node.status_color} aria-hidden="true" />{node.status}</span>
        <span className="flex w-full flex-wrap gap-1">{node.capabilities.map((id) => <Badge key={id} variant="outline">{capabilities?.find((capability) => capability.id === id)?.label ?? id}</Badge>)}</span>
        {node.needs_redeploy && <span className="text-muted-foreground text-xs">Redeploy to apply</span>}
      </button>
    </FlowNode>
  );
}

function ServerBand({ data }: NodeProps<TopologyFlowNode>) {
  return (
    <button type="button" className="nodrag flex items-center gap-2 rounded-md px-3 py-2 text-sm font-medium focus-visible:outline-2 focus-visible:outline-ring" aria-label={`Open server ${data.node.name}, ${data.node.status}`}>
      <ServerIcon className="size-4" /><span className="max-w-96 truncate" title={data.node.name}>{data.node.name}</span><StatusRipple variant={data.node.status_color} aria-hidden="true" /><span className="text-muted-foreground">{data.node.status}</span>
    </button>
  );
}

const nodeTypes = { topology: TopologyCard, group: ServerBand };

function layout(topology: EnvironmentTopology): TopologyFlowNode[] {
  const nodes: TopologyFlowNode[] = [];
  const positions = new Map<string, number>();
  let y = 20;
  for (const server of topology.nodes.filter((node) => node.type === 'server')) {
    const children = topology.nodes.filter((node) => node.server_id === server.server_id && !['server', 'domain', 'external'].includes(node.type));
    const counts = new Map<number, number>([[2, children.filter((node) => columns[node.type] === 1).length]]);
    const rows = children.map((node) => {
      const row = counts.get(columns[node.type]) ?? 0;
      counts.set(columns[node.type], row + 1);
      return row;
    });
    const height = 60 + Math.max(1, ...rows.map((row) => row + 1)) * 160;
    nodes.push({ id: server.id, type: 'group', data: { node: server }, position: { x: 300, y }, style: { width: 860, height }, className: '!bg-muted/30 !border-border !rounded-lg', focusable: false });
    children.forEach((node, index) => {
      positions.set(node.id, y + 60 + rows[index] * 160);
      nodes.push({ id: node.id, type: 'topology', data: { node }, parentId: server.id, extent: 'parent', position: { x: 20 + (columns[node.type] - 1) * 280, y: 60 + rows[index] * 160 }, focusable: false });
    });
    y += height + 30;
  }
  const targetY = (node: TopologyNode) => positions.get(topology.edges.find((edge) => edge.source === node.id)?.target ?? '');
  let domainY = 80;
  for (const node of topology.nodes.filter((item) => item.type === 'domain').sort((a, b) => (targetY(a) ?? Number.MAX_SAFE_INTEGER) - (targetY(b) ?? Number.MAX_SAFE_INTEGER))) {
    domainY = Math.max(domainY, targetY(node) ?? domainY);
    nodes.push({ id: node.id, type: 'topology', data: { node }, position: { x: 20, y: domainY }, focusable: false });
    domainY += 160;
  }
  topology.nodes.filter((node) => node.type === 'external').forEach((node, index) => {
    nodes.push({ id: node.id, type: 'topology', data: { node }, position: { x: 600, y: y + index * 160 }, focusable: false });
  });
  return nodes;
}

export default function Topology({ topology }: { topology: EnvironmentTopology }) {
  const { getActualAppearance } = useAppearance();
  const nodes = useMemo(() => layout(topology), [topology]);

  return (
    <div className="h-[32rem] overflow-hidden rounded-lg border" role="region" aria-label="Environment topology">
      <ReactFlow<TopologyFlowNode>
        nodes={nodes}
        edges={topology.edges}
        nodeTypes={nodeTypes}
        defaultEdgeOptions={defaultEdgeOptions}
        nodesDraggable={false}
        nodesConnectable={false}
        elementsSelectable={false}
        edgesFocusable={false}
        deleteKeyCode={null}
        onNodeClick={(_, node) => router.visit(node.data.node.url)}
        fitView
        colorMode={getActualAppearance()}
      >
        <Background variant={BackgroundVariant.Dots} />
        <Controls position="bottom-right" showInteractive={false} />
      </ReactFlow>
    </div>
  );
}
