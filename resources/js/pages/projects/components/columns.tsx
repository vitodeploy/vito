import DateTime from '@/components/date-time';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import DeleteProject from '@/pages/projects/components/delete-project';
import Users from '@/pages/projects/components/users';
import ProjectForm from '@/pages/projects/components/project-form';
import { SharedData } from '@/types';
import type { Project } from '@/types/project';
import { usePage } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { MoreVerticalIcon } from 'lucide-react';
import LeaveProject from '@/pages/projects/components/leave-project';

const CurrentProject = ({ project }: { project: Project }) => {
  const page = usePage<SharedData>();
  return <>{project.id === page.props.auth.currentProject?.id && <Badge variant="default">current</Badge>}</>;
};

export const columns: ColumnDef<Project>[] = [
  {
    accessorKey: 'name',
    header: 'Name',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return (
        <div className="flex items-center space-x-1">
          <span>{row.original.name}</span> <CurrentProject project={row.original} />
        </div>
      );
    },
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
              <Users project={row.original}>
                <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Users</DropdownMenuItem>
              </Users>
              <ProjectForm project={row.original}>
                <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Edit</DropdownMenuItem>
              </ProjectForm>
              {row.original.role !== 'owner' && (
                <LeaveProject project={row.original}>
                  <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Leave project</DropdownMenuItem>
                </LeaveProject>
              )}
              {row.original.role === 'owner' && (
                <>
                  <DropdownMenuSeparator />
                  <DeleteProject project={row.original}>
                    <DropdownMenuItem onSelect={(e) => e.preventDefault()} variant="destructive">
                      Delete Project
                    </DropdownMenuItem>
                  </DeleteProject>
                </>
              )}
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];
