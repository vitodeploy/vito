import { Server } from '@/types/server';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { MoreVerticalIcon } from 'lucide-react';
import DeleteServer from '@/pages/servers/partials/delete-server';

export default function ServerActions({ server }: { server: Server }) {
  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" className="h-8 w-8 p-0">
          <span className="sr-only">Open menu</span>
          <MoreVerticalIcon />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        <DropdownMenuItem>Copy payment ID</DropdownMenuItem>
        <DropdownMenuSeparator />
        <DeleteServer server={server}>
          <DropdownMenuItem onSelect={(e) => e.preventDefault()} variant="destructive">
            Delete Server
          </DropdownMenuItem>
        </DeleteServer>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
