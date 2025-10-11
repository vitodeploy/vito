import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { WorkflowAction } from '@/types/workflow-action';
import { MessageCircleQuestionIcon } from 'lucide-react';
import ActionForm from './action-form';

interface Props {
  actions: {
    [key: string]: WorkflowAction;
  };
  onActionAdded: (action: WorkflowAction) => void;
}

export default function Actions({ actions, onActionAdded }: Props) {
  return (
    <div className="bg-background absolute top-0 right-0 z-10 m-2 h-[215px] w-[200px] overflow-y-auto rounded-lg border p-3">
      <div className="flex flex-col gap-2">
        <div className="flex items-center justify-between gap-1 border-b pb-2">
          <h3 className="text-muted-foreground">Actions</h3>
          <Tooltip>
            <TooltipTrigger>
              <MessageCircleQuestionIcon className="size-4" />
            </TooltipTrigger>
            <TooltipContent side="bottom" className="mt-2 mr-4 w-[180px]">
              <div>Click on each action to add them to the workflow</div>
            </TooltipContent>
          </Tooltip>
        </div>
        {Object.entries(actions).map(([key, action]) => (
          <ActionForm key={`add-action-${key}`} action={action} onActionChanged={onActionAdded} type="add">
            <div key={`action-${key}`} className="hover:bg-accent cursor-pointer rounded border px-2 py-1">
              <p className="font-normal">{action.label}</p>
              {action.description && <p className="text-muted-foreground text-sm">{action.description}</p>}
            </div>
          </ActionForm>
        ))}
      </div>
    </div>
  );
}
