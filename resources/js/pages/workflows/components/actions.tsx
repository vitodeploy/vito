import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { WorkflowAction } from '@/types/workflow-action';
import { MessageCircleQuestionIcon } from 'lucide-react';
import ActionForm from './action-form';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';

interface Props {
  actions: {
    [key: string]: WorkflowAction;
  };
  onActionAdded: (action: WorkflowAction) => void;
}

export default function Actions({ actions, onActionAdded }: Props) {
  return (
    <div className="absolute top-0 right-0 z-10 m-2 w-[200px] overflow-y-auto rounded-lg border p-0">
      <Command className="bg-background">
        <CommandInput
          right={
            <Tooltip>
              <TooltipTrigger>
                <MessageCircleQuestionIcon className="text-muted-foreground size-4" />
              </TooltipTrigger>
              <TooltipContent side="bottom" className="mt-2 mr-3 w-[190px]">
                <div>Click on each action to add them to the workflow</div>
              </TooltipContent>
            </Tooltip>
          }
        />
        <CommandList className="bg-transparent p-0">
          <CommandEmpty>No results</CommandEmpty>
          <CommandGroup>
            {Object.entries(actions).map(([key, action]) => (
              <CommandItem key={`cmd-item-${key}`} value={key} className="p-0">
                <ActionForm action={action} onActionChanged={onActionAdded} type="add">
                  <div key={`action-${key}`} className="w-full p-2">
                    <p className="font-normal">{action.label}</p>
                    {action.description && <p className="text-muted-foreground text-sm">{action.description}</p>}
                  </div>
                </ActionForm>
              </CommandItem>
            ))}
          </CommandGroup>
        </CommandList>
      </Command>
    </div>
  );
}
