import { ColumnDef } from '@tanstack/react-table';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { MoreVerticalIcon, PlayIcon } from 'lucide-react';
import { Command } from '@/types/command';
import CopyableBadge from '@/components/copyable-badge';
import Execute from '@/pages/commands/components/execute';
import { useDialog } from '@/hooks/use-dialog';

function Edit({ command }: { command: Command }) {
  const dialog = useDialog();

  return <DropdownMenuItem onSelect={() => dialog.commandEdit.open({ command })}>Edit</DropdownMenuItem>;
}

function Delete({ command }: { command: Command }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      variant="destructive"
      onSelect={() =>
        dialog.confirm.open({
          title: 'Delete command',
          description: 'Are you sure you want to delete this command?',
          variant: 'destructive',
          confirmLabel: 'Delete',
          method: 'delete',
          url: route('commands.destroy', { server: command.server_id, site: command.site_id, command: command.id }),
        })
      }
    >
      Delete
    </DropdownMenuItem>
  );
}

export const columns: ColumnDef<Command>[] = [
  {
    accessorKey: 'name',
    header: 'Name',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    accessorKey: 'command',
    header: 'Command',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return <CopyableBadge text={row.original.command} />;
    },
  },
  {
    id: 'actions',
    enableColumnFilter: false,
    enableSorting: false,
    cell: ({ row }) => {
      return (
        <div className="flex items-center justify-end gap-1">
          <Execute command={row.original}>
            <Button variant="outline" className="size-8">
              <PlayIcon className="size-3" />
            </Button>
          </Execute>
          <DropdownMenu modal={false}>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                <span className="sr-only">Open menu</span>
                <MoreVerticalIcon />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <Edit command={row.original} />
              <Link
                href={route('commands.show', {
                  server: row.original.server_id,
                  site: row.original.site_id,
                  command: row.original.id,
                })}
              >
                <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Executions</DropdownMenuItem>
              </Link>
              <DropdownMenuSeparator />
              <Delete command={row.original} />
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];
