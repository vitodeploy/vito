import { ColumnDef } from '@tanstack/react-table';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { MoreVerticalIcon } from 'lucide-react';
import { ScriptEventHook } from '@/types/script-event-hook';
import { Script } from '@/types/script';
import { useDialog } from '@/hooks/use-dialog';
import { Badge } from '@/components/ui/badge';

function Actions({ hook, script }: { hook: ScriptEventHook; script: Script }) {
  const dialog = useDialog();

  return (
    <DropdownMenu modal={false}>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" className="h-8 w-8 p-0">
          <span className="sr-only">Open menu</span>
          <MoreVerticalIcon />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        <DropdownMenuItem onSelect={() => dialog.scriptHookForm.open({ script, hook })}>Edit</DropdownMenuItem>
        <DropdownMenuSeparator />
        <DropdownMenuItem
          variant="destructive"
          onSelect={() =>
            dialog.confirm.open({
              title: 'Delete hook',
              description: 'Are you sure you want to delete this event hook?',
              variant: 'destructive',
              confirmLabel: 'Delete',
              method: 'delete',
              url: route('scripts.hooks.destroy', { script: hook.script_id, hook: hook.id }),
            })
          }
        >
          Delete
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}

export function hookColumns(script: Script): ColumnDef<ScriptEventHook>[] {
  return [
    {
      accessorKey: 'event',
      header: 'Event',
      cell: ({ row }) => (
        <Badge variant={row.original.event_color}>
          {row.original.event}
        </Badge>
      ),
    },
    {
      accessorKey: 'server',
      header: 'Server',
      cell: ({ row }) => row.original.server?.name ?? row.original.server_id,
    },
    {
      accessorKey: 'user',
      header: 'SSH User',
    },
    {
      accessorKey: 'enabled',
      header: 'Enabled',
      cell: ({ row }) => (row.original.enabled ? 'Yes' : 'No'),
    },
    {
      id: 'actions',
      enableColumnFilter: false,
      enableSorting: false,
      cell: ({ row }) => (
        <div className="flex items-center justify-end">
          <Actions hook={row.original} script={script} />
        </div>
      ),
    },
  ];
}
