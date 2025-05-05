import { Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { ChevronsUpDownIcon, PlusIcon } from 'lucide-react';
import { useInitials } from '@/hooks/use-initials';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { type Server } from '@/types/server';
import type { SharedData } from '@/types';

export function ServerSwitch() {
  const page = usePage<SharedData>();
  const [selectedServer, setSelectedServer] = useState(page.props.server || null);
  const initials = useInitials();
  const form = useForm();

  const handleServerChange = (server: Server) => {
    setSelectedServer(server);
    form.post(route('servers.switch', { server: server.id }));
  };

  return (
    <div className="flex items-center">
      {selectedServer && (
        <Link href={route('servers.show', { server: selectedServer?.id || '' })}>
          <Button variant="ghost" className="px-2">
            <Avatar className="size-7 rounded-md">
              <AvatarFallback className="rounded-md">{initials(selectedServer?.name ?? '')}</AvatarFallback>
            </Avatar>
            <span className="hidden lg:flex">{selectedServer?.name}</span>
          </Button>
        </Link>
      )}

      {!selectedServer && (
        <Button variant="ghost" className="px-2">
          <Avatar className="size-7 rounded-md">
            <AvatarFallback className="rounded-md">S</AvatarFallback>
          </Avatar>
          <span className="hidden lg:flex">Select a server</span>
        </Button>
      )}

      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button variant="ghost" className="px-1!">
            <ChevronsUpDownIcon size={5} />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent className="w-56" align="start">
          {page.props.projectServers.map((server) => (
            <DropdownMenuCheckboxItem
              key={`server-${server.id.toString()}`}
              checked={selectedServer?.id === server.id}
              onCheckedChange={() => handleServerChange(server)}
            >
              {server.name}
            </DropdownMenuCheckboxItem>
          ))}
          <DropdownMenuSeparator />
          <DropdownMenuItem className="gap-0">
            <PlusIcon size={5} />
            <span className="ml-2">Create new server</span>
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  );
}
