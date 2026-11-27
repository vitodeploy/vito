import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { WorkflowAction } from '@/types/workflow-action';

export default function Actions({ actions, onSelect }: { actions: Record<string, WorkflowAction>; onSelect: (action: WorkflowAction) => void }) {
  const categories = Object.entries(actions).reduce<Record<string, [string, WorkflowAction][]>>((groups, entry) => {
    const category = entry[1].category || 'other';
    (groups[category] ??= []).push(entry);
    return groups;
  }, {});

  return (
    <div className="absolute top-0 right-0 z-10 m-2 w-[220px] overflow-hidden rounded-lg border">
      <Command className="bg-background">
        <CommandInput placeholder="Add an action..." />
        <CommandList className="max-h-[calc(100vh-12rem)] p-0">
          <CommandEmpty>No results</CommandEmpty>
          {Object.entries(categories).map(([category, items]) => (
            <CommandGroup key={`category-${category}`} heading={category} className="capitalize">
              {items.map(([key, action]) => (
                <CommandItem key={`action-${key}`} value={`${key} ${action.label}`} onSelect={() => onSelect(action)} className="normal-case">
                  <div className="flex flex-col">
                    <span>{action.label}</span>
                    {action.description && <span className="text-muted-foreground text-xs">{action.description}</span>}
                  </div>
                </CommandItem>
              ))}
            </CommandGroup>
          ))}
        </CommandList>
      </Command>
    </div>
  );
}
