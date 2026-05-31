import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { ColumnDef } from '@tanstack/react-table';
import { MoreVerticalIcon } from 'lucide-react';
import { ProjectUser } from '@/types/project-user';
import { Badge } from '@/components/ui/badge';
import { useDialog } from '@/hooks/use-dialog';

function Actions({ invitation }: { invitation: ProjectUser }) {
  const dialog = useDialog();

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
          <DropdownMenuItem
            onSelect={(e) => {
              e.preventDefault();
              window.location.href = `/settings/projects/${invitation.project_id}/invitations/accept`;
            }}
          >
            Accept & Join
          </DropdownMenuItem>
          <DropdownMenuSeparator />
          <DropdownMenuItem
            variant="destructive"
            onSelect={() =>
              dialog.confirm.open({
                title: `Reject invitation to ${invitation.project_name}`,
                description: 'Are you sure you want to reject joining this project?',
                variant: 'destructive',
                confirmLabel: 'Reject',
                method: 'delete',
                url: `/settings/projects/${invitation.project_id}/leave`,
              })
            }
          >
            Reject
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  );
}

export const columns: ColumnDef<ProjectUser>[] = [
  {
    accessorKey: 'project_name',
    header: 'Project name',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    accessorKey: 'role',
    header: 'Role',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return <Badge variant="outline">{row.original.role}</Badge>;
    },
  },
  {
    id: 'actions',
    enableColumnFilter: false,
    enableSorting: false,
    cell: ({ row }) => {
      return <Actions invitation={row.original} />;
    },
  },
];
