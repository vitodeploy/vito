import { ColumnDef } from '@tanstack/react-table';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { MoreVerticalIcon, PlayIcon } from 'lucide-react';
import { Script } from '@/types/script';
import Execute from '@/pages/scripts/components/execute';
import { useDialog } from '@/hooks/use-dialog';

function Edit({ script }: { script: Script }) {
  const dialog = useDialog();

  return <DropdownMenuItem onSelect={() => dialog.scriptForm.open({ script })}>Edit</DropdownMenuItem>;
}

function Delete({ script }: { script: Script }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      variant="destructive"
      onSelect={() =>
        dialog.confirm.open({
          title: 'Delete script',
          description: 'Are you sure you want to delete this script?',
          variant: 'destructive',
          confirmLabel: 'Delete',
          method: 'delete',
          url: route('scripts.destroy', { server: script.server_id, site: script.site_id, script: script.id }),
        })
      }
    >
      Delete
    </DropdownMenuItem>
  );
}

export const columns: ColumnDef<Script>[] = [
  {
    accessorKey: 'name',
    header: 'Name',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    id: 'actions',
    enableColumnFilter: false,
    enableSorting: false,
    cell: ({ row }) => {
      return (
        <div className="flex items-center justify-end gap-1">
          <Execute script={row.original}>
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
              <Edit script={row.original} />
              <Link
                href={route('scripts.show', {
                  script: row.original.id,
                })}
              >
                <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Executions</DropdownMenuItem>
              </Link>
              <DropdownMenuSeparator />
              <Delete script={row.original} />
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];
