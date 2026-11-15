import { ReactNode } from 'react';
import { Handle, Position } from '@xyflow/react';
import { cn } from '@/lib/utils';

export default function FlowNode({ children, className, connectable = true }: { children: ReactNode; className?: string; connectable?: boolean }) {
  return (
    <div className={cn('bg-card hover:border-primary relative rounded-md border px-6 py-3', className)}>
      {children}
      <Handle
        type="target"
        position={Position.Left}
        isConnectable={connectable}
        className="!border-muted hover:!bg-primary hover:!border-primary !size-2 !bg-neutral-400 dark:!bg-neutral-600"
      />
      <Handle
        type="source"
        position={Position.Right}
        isConnectable={connectable}
        className="!border-muted hover:!bg-primary hover:!border-primary !size-2 !bg-neutral-400 dark:!bg-neutral-600"
      />
    </div>
  );
}
