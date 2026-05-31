import { ColumnDef } from '@tanstack/react-table';
import DateTime from '@/components/date-time';
import { SourceControl } from '@/types/source-control';
import { Badge } from '@/components/ui/badge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { MoreVerticalIcon } from 'lucide-react';
import { useDialog } from '@/hooks/use-dialog';

function Edit({ sourceControl }: { sourceControl: SourceControl }) {
  const dialog = useDialog();

  return <DropdownMenuItem onSelect={() => dialog.sourceControlEdit.open({ sourceControl })}>Edit</DropdownMenuItem>;
}

function Delete({ sourceControl }: { sourceControl: SourceControl }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      variant="destructive"
      onSelect={() =>
        dialog.confirm.open({
          title: `Delete ${sourceControl.name}`,
          description: `Are you sure you want to delete ${sourceControl.name}?`,
          variant: 'destructive',
          confirmLabel: 'Delete',
          method: 'delete',
          url: route('source-controls.destroy', sourceControl.id),
        })
      }
    >
      Delete
    </DropdownMenuItem>
  );
}

export const columns: ColumnDef<SourceControl>[] = [
  {
    accessorKey: 'id',
    header: 'ID',
    enableColumnFilter: true,
    enableSorting: true,
    enableHiding: true,
  },
  {
    accessorKey: 'provider',
    header: 'Provider',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    accessorKey: 'name',
    header: 'Name',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    accessorKey: 'global',
    header: 'Global',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return <div>{row.original.global ? <Badge variant="success">yes</Badge> : <Badge variant="danger">no</Badge>}</div>;
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
    id: 'actions',
    enableColumnFilter: false,
    enableSorting: false,
    cell: ({ row }) => {
      const isGithubApp = row.original.provider === 'github-app';
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
              <Edit sourceControl={row.original} />
              {isGithubApp && row.original.github_app?.html_url && (
                <>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem asChild>
                    <a href={row.original.github_app.html_url} target="_blank" rel="noopener noreferrer">
                      Manage permissions
                    </a>
                  </DropdownMenuItem>
                </>
              )}
              {!isGithubApp && (
                <>
                  <DropdownMenuSeparator />
                  <Delete sourceControl={row.original} />
                </>
              )}
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];
