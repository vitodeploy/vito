import { ColumnDef } from '@tanstack/react-table';
import DateTime from '@/components/date-time';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { MoreVerticalIcon } from 'lucide-react';
import { DatabaseUser } from '@/types/database-user';
import { Badge } from '@/components/ui/badge';
import { useDialog } from '@/hooks/use-dialog';

function Link({ databaseUser }: { databaseUser: DatabaseUser }) {
  const dialog = useDialog();

  return <DropdownMenuItem onSelect={() => dialog.databaseUserLink.open({ databaseUser })}>Link</DropdownMenuItem>;
}

function Edit({ databaseUser }: { databaseUser: DatabaseUser }) {
  const dialog = useDialog();

  return <DropdownMenuItem onSelect={() => dialog.databaseUserEdit.open({ databaseUser })}>Edit</DropdownMenuItem>;
}

function Delete({ databaseUser }: { databaseUser: DatabaseUser }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      variant="destructive"
      onSelect={() =>
        dialog.confirm.open({
          title: `Delete database user [${databaseUser.username}]`,
          description: `Are you sure you want to delete database user ${databaseUser.username}? This action cannot be undone.`,
          variant: 'destructive',
          confirmLabel: 'Delete',
          method: 'delete',
          url: route('database-users.destroy', { server: databaseUser.server_id, databaseUser: databaseUser.id }),
        })
      }
    >
      Delete
    </DropdownMenuItem>
  );
}

export const columns: ColumnDef<DatabaseUser>[] = [
  {
    accessorKey: 'username',
    header: 'Username',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    accessorKey: 'permission',
    header: 'Permission',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return <Badge variant="outline">{row.original.permission}</Badge>;
    },
  },
  {
    accessorKey: 'databases',
    header: 'Linked databases',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return (
        <div className="flex items-center">
          {row.original.databases.map((database) => (
            <Badge key={database} variant="outline" className="mr-1">
              {database}
            </Badge>
          ))}
        </div>
      );
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
              <Edit databaseUser={row.original} />
              <Link databaseUser={row.original} />
              <DropdownMenuSeparator />
              <Delete databaseUser={row.original} />
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];
