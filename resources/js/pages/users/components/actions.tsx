import { User } from '@/types/user';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { MoreVerticalIcon } from 'lucide-react';
import DeleteUser from '@/pages/users/components/delete-user';
import Projects from '@/pages/users/components/projects';
import UserForm from '@/pages/users/components/user-form';

export default function UserActions({ user }: { user: User }) {
  return (
    <DropdownMenu modal={false}>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" className="h-8 w-8 p-0">
          <span className="sr-only">Open menu</span>
          <MoreVerticalIcon />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        <UserForm user={user}>
          <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Edit</DropdownMenuItem>
        </UserForm>
        <Projects user={user}>
          <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Projects</DropdownMenuItem>
        </Projects>
        <DropdownMenuSeparator />
        <DeleteUser user={user}>
          <DropdownMenuItem onSelect={(e) => e.preventDefault()} variant="destructive">
            Delete
          </DropdownMenuItem>
        </DeleteUser>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
