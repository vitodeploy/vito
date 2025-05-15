import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { MoreVerticalIcon } from 'lucide-react';
import { Project } from '@/types/project';
import DeleteProject from '@/pages/projects/components/delete-project';
import UsersAction from '@/pages/projects/components/users-action';

export default function ProjectActions({ project }: { project: Project }) {
  return (
    <DropdownMenu modal={false}>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" className="h-8 w-8 p-0">
          <span className="sr-only">Open menu</span>
          <MoreVerticalIcon />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        <UsersAction project={project}>
          <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Users</DropdownMenuItem>
        </UsersAction>
        <DropdownMenuSeparator />
        <DeleteProject project={project}>
          <DropdownMenuItem onSelect={(e) => e.preventDefault()} variant="destructive">
            Delete Project
          </DropdownMenuItem>
        </DeleteProject>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
