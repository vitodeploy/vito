import { useForm, usePage } from '@inertiajs/react';
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
import CreateServer from '@/pages/servers/components/create-server';

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
      <DropdownMenu modal={false}>
        <DropdownMenuTrigger asChild>
          <Button variant="ghost" className="px-1!">
            {selectedServer && (
              <>
                <Avatar className="size-6 rounded-sm">
                  <AvatarFallback className="rounded-sm">{initials(selectedServer?.name ?? '')}</AvatarFallback>
                </Avatar>
                <span className="hidden lg:flex">{selectedServer?.name}</span>
              </>
            )}

            {!selectedServer && (
              <>
                <Avatar className="size-6 rounded-sm">
                  <AvatarFallback className="rounded-sm">S</AvatarFallback>
                </Avatar>
                <span className="hidden lg:flex">Select a server</span>
              </>
            )}

            <ChevronsUpDownIcon size={5} />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent className="w-56" align="start">
          {page.props.project_servers.length > 0 ? (
            page.props.project_servers.map((server) => (
              <DropdownMenuCheckboxItem
                key={`server-${server.id.toString()}`}
                checked={selectedServer?.id === server.id}
                onCheckedChange={() => handleServerChange(server)}
              >
                {server.name}
              </DropdownMenuCheckboxItem>
            ))
          ) : (
            <DropdownMenuItem disabled>No servers</DropdownMenuItem>
          )}
          <DropdownMenuSeparator />
          <CreateServer>
            <DropdownMenuItem className="gap-0" onSelect={(e) => e.preventDefault()}>
              <div className="flex items-center">
                <PlusIcon size={5} />
                <span className="ml-2">Create new server</span>
              </div>
            </DropdownMenuItem>
          </CreateServer>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  );
}
