import { memo } from 'react';
import { type Node, type NodeProps, useReactFlow } from '@xyflow/react';
import { CopyIcon, CopyPlusIcon, FlagIcon, PencilIcon, TrashIcon } from 'lucide-react';
import FlowNode from '@/components/flow-node';
import { cn } from '@/lib/utils';
import { WorkflowNodeData } from '@/types/workflow-node-data';
import { useWorkflowEditor } from './editor-context';
import ToolbarButton from './toolbar-button';

function CustomNode({ id, selected, data }: NodeProps<Node<WorkflowNodeData>>) {
  const editor = useWorkflowEditor();
  const { deleteElements } = useReactFlow();
  const actionLabel = editor.actionFor(data.action.handler)?.label;

  return (
    <FlowNode className={selected ? 'border-primary' : data.action.starting ? 'border-success' : ''}>
      {selected && (
        <div
          className="nodrag bg-card absolute -top-[47px] -right-[1px] flex items-center gap-1 rounded-md border p-1"
          onDoubleClick={(e) => e.stopPropagation()}
        >
          <ToolbarButton
            label="Starting action"
            onClick={() => editor.setStartingNode(id)}
            className={cn('hover:text-success', data.action.starting && 'text-success')}
          >
            <FlagIcon className="size-4" />
          </ToolbarButton>
          <ToolbarButton label="Edit" onClick={() => editor.editNode(id)}>
            <PencilIcon className="size-4" />
          </ToolbarButton>
          <ToolbarButton label="Copy" onClick={() => editor.copyNodes([id])}>
            <CopyIcon className="size-4" />
          </ToolbarButton>
          <ToolbarButton label="Duplicate" onClick={() => editor.duplicateNodes([id])}>
            <CopyPlusIcon className="size-4" />
          </ToolbarButton>
          <ToolbarButton label="Delete" onClick={() => deleteElements({ nodes: [{ id }] })} className="hover:text-destructive">
            <TrashIcon className="size-4" />
          </ToolbarButton>
        </div>
      )}
      <div className="flex flex-col">
        <span className="text-md">{data.label}</span>
        {actionLabel && actionLabel !== data.label && <span className="text-muted-foreground text-xs">{actionLabel}</span>}
      </div>
    </FlowNode>
  );
}

export default memo(CustomNode);
