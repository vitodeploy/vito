import { ColumnDef } from '@tanstack/react-table';
import DateTime from '@/components/date-time';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { MoreVerticalIcon } from 'lucide-react';
import { ApiKey } from '@/types/api-key';
import { Badge } from '@/components/ui/badge';
import { Project } from '@/types/project';
import { useDialog } from '@/hooks/use-dialog';

function Delete({ apiKey }: { apiKey: ApiKey }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      variant="destructive"
      onSelect={() =>
        dialog.confirm.open({
          title: `Delete ${apiKey.name}`,
          description: `Are you sure you want to delete ${apiKey.name}?`,
          variant: 'destructive',
          confirmLabel: 'Delete',
          method: 'delete',
          url: route('api-keys.destroy', apiKey.id),
        })
      }
    >
      Delete
    </DropdownMenuItem>
  );
}

export function getColumns(projects: Project[]): ColumnDef<ApiKey>[] {
  return [
    {
      accessorKey: 'name',
      header: 'Name',
      enableColumnFilter: true,
      enableSorting: true,
    },
    {
      accessorKey: 'permissions',
      header: 'Permissions',
      enableColumnFilter: true,
      enableSorting: true,
      cell: ({ row }) => {
        return row.original.permissions.includes('write') ? <span>read & write</span> : <span>read</span>;
      },
    },
    {
      accessorKey: 'project_ids',
      header: 'Projects',
      enableColumnFilter: false,
      enableSorting: false,
      cell: ({ row }) => {
        const projectIds = row.original.project_ids;
        if (!projectIds || projectIds.length === 0) {
          return <Badge variant="outline">All projects</Badge>;
        }
        return (
          <div className="flex flex-wrap gap-1">
            {projectIds.map((id) => {
              const project = projects.find((p) => p.id === id);
              return (
                <Badge key={id} variant="default">
                  {project?.name ?? `Project #${id}`}
                </Badge>
              );
            })}
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
                <Delete apiKey={row.original} />
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        );
      },
    },
  ];
}
