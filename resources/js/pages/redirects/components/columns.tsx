import { ColumnDef } from '@tanstack/react-table';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { MoreVerticalIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import DateTime from '@/components/date-time';
import { Redirect } from '@/types/redirect';
import { useDialog } from '@/hooks/use-dialog';

function Edit({ redirect }: { redirect: Redirect }) {
  const dialog = useDialog();

  return <DropdownMenuItem onSelect={() => dialog.redirectEdit.open({ redirect })}>Edit</DropdownMenuItem>;
}

function Delete({ redirect }: { redirect: Redirect }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      variant="destructive"
      onSelect={() =>
        dialog.confirm.open({
          title: 'Delete Redirect',
          description: 'Are you sure you want to delete this redirect?',
          variant: 'destructive',
          confirmLabel: 'Delete',
          method: 'delete',
          url: route('redirects.destroy', { server: redirect.server_id, site: redirect.site_id, redirect: redirect.id }),
        })
      }
    >
      Delete
    </DropdownMenuItem>
  );
}

export const columns: ColumnDef<Redirect>[] = [
  {
    accessorKey: 'from',
    header: 'From',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    accessorKey: 'to',
    header: 'To',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    accessorKey: 'mode',
    header: 'Mode',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return String(row.original.mode) === '1000' ? 'Proxy' : row.original.mode;
    },
  },
  {
    accessorKey: 'created_at',
    header: 'Created at',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return <DateTime date={row.original.created_at} />;
    },
  },
  {
    accessorKey: 'status',
    header: 'Status',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return <Badge variant={row.original.status_color}>{row.original.status}</Badge>;
    },
  },
  {
    id: 'actions',
    enableColumnFilter: false,
    enableSorting: false,
    cell: ({ row }) => {
      return (
        <div className="flex items-center justify-end">
          <DropdownMenu modal={false}>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                <span className="sr-only">Open menu</span>
                <MoreVerticalIcon />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <Edit redirect={row.original} />
              <Delete redirect={row.original} />
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];
