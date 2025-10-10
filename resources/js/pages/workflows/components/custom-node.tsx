import { cn } from '@/lib/utils';
import { WorkflowNodeData } from '@/types/workflow-node-data';
import { Handle, Position } from '@xyflow/react';
import { memo } from 'react';

function CustomNode({ selected, data }: { selected: boolean; data: WorkflowNodeData }) {
  return (
    <div className={cn('bg-card hover:border-primary rounded-md border px-6 py-3', selected ? 'border-primary' : 'border-border')}>
      <div className="flex">
        <div className="text-md">{data.label}</div>
      </div>

      <Handle
        type="target"
        position={Position.Left}
        className="!border-muted hover:!bg-primary hover:!border-primary !size-2 !bg-neutral-400 dark:!bg-neutral-600"
      />
      <Handle
        type="source"
        position={Position.Right}
        className="!border-muted hover:!bg-primary hover:!border-primary !size-2 !bg-neutral-400 dark:!bg-neutral-600"
      />
    </div>
  );
}

export default memo(CustomNode);
