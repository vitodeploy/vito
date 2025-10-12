import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { WorkflowNodeData } from '@/types/workflow-node-data';
import { Handle, Position, useReactFlow } from '@xyflow/react';
import { FlagIcon, PencilIcon, TrashIcon } from 'lucide-react';
import { memo } from 'react';
import ActionForm from './action-form';
import { WorkflowAction } from '@/types/workflow-action';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';

function CustomNode({ id, selected, data }: { id: string; selected: boolean; data: WorkflowNodeData }) {
  console.log(data);
  const { getNode, deleteElements, setNodes, getEdges } = useReactFlow();

  const handleDelete = () => {
    deleteElements({ nodes: [{ id }] });
  };

  const getIncomingNodeActions = (): WorkflowAction[] => {
    return getEdges()
      .filter((edge) => edge.target === id)
      .map((edge) => getNode(edge.source))
      .filter((node) => node !== null)
      .map((node) => node?.data.action as WorkflowAction);
  };

  const handleActionChange = (newAction: WorkflowAction) => {
    const node = getNode(id);
    if (node) {
      node.data.label = newAction.label;
      node.data.action = newAction;
      setNodes((nodes) =>
        nodes.map((n) =>
          n.id === id
            ? {
                ...node,
                data: {
                  label: newAction.label,
                  action: newAction,
                },
              }
            : n,
        ),
      );
    }
  };

  const makeStartingNode = () => {
    const node = getNode(id);
    if (node) {
      /*@ts-expect-error dynamic types*/
      node.data.action.starting = true;
      data.action.starting = true;
      setNodes((nodes) =>
        nodes.map((n) =>
          n.id === id
            ? {
                ...node,
              }
            : {
                ...n,
                data: {
                  ...n.data,
                  action: {
                    /*@ts-expect-error dynamic types*/
                    ...n.data.action,
                    starting: false,
                  },
                },
              },
        ),
      );
    }
  };

  return (
    <div
      className={cn(
        'bg-card hover:border-primary relative rounded-md border px-6 py-3',
        selected ? 'border-primary' : data.action.starting ? 'border-success' : '',
      )}
    >
      {selected && (
        <div className="bg-card absolute -top-[47px] -right-[1px] rounded-md border p-1">
          <div className="flex items-center gap-2">
            <Tooltip>
              <TooltipTrigger asChild>
                <Button
                  variant="ghost"
                  onClick={makeStartingNode}
                  className={cn('hover:text-success size-7', data.action.starting ? 'text-success' : '')}
                >
                  <FlagIcon className="size-4" />
                </Button>
              </TooltipTrigger>
              <TooltipContent>Starting node</TooltipContent>
            </Tooltip>
            <ActionForm action={data.action} connectedActions={getIncomingNodeActions()} onActionChanged={handleActionChange} type="edit">
              <Button variant="ghost" className="size-7">
                <PencilIcon className="size-4" />
              </Button>
            </ActionForm>
            <Button variant="ghost" className="hover:text-destructive size-7" onClick={handleDelete}>
              <TrashIcon className="size-4" />
            </Button>
          </div>
        </div>
      )}
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
