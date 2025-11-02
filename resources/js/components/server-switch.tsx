import { type SharedData } from '@/types';
import { type Server } from '@/types/server';
import { useForm, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { ChevronsUpDownIcon, PlusIcon } from 'lucide-react';
import { useInitials } from '@/hooks/use-initials';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import CreateServer from '@/pages/servers/components/create-server';
import ServerSelect from '@/pages/servers/components/server-select';
import { CommandGroup, CommandItem } from '@/components/ui/command';

export function ServerSwitch() {
  const page = usePage<SharedData>();
  const [open, setOpen] = useState(false);
  const [serverFormOpen, setServerFormOpen] = useState(false);
  const [selected, setSelected] = useState<string>(page.props.server?.id?.toString() ?? '');
  const initials = useInitials();
  const form = useForm();

  useEffect(() => {
    setSelected(page.props.server?.id?.toString() ?? '');
  }, [page.props.server?.id]);

  const handleServerChange = (value: string, server: Server) => {
    setSelected(value);
    setOpen(false);
    form.post(route('servers.switch', { server: server.id }));
  };

  const footer = (
    <CommandGroup>
      <CreateServer defaultOpen={serverFormOpen} onOpenChange={setServerFormOpen}>
        <CommandItem
          value="create-server"
          onSelect={() => {
            setServerFormOpen(true);
          }}
          className="gap-0"
        >
          <div className="flex items-center">
            <PlusIcon size={5} />
            <span className="ml-2">Create new server</span>
          </div>
        </CommandItem>
      </CreateServer>
    </CommandGroup>
  );

  const trigger = (
    <Button variant="ghost" className="px-1!">
      {page.props.server ? (
        <>
          <Avatar className="size-6 rounded-sm">
            <AvatarFallback className="rounded-sm">{initials(page.props.server?.name ?? '')}</AvatarFallback>
          </Avatar>
          <span className="hidden lg:flex">{page.props.server?.name}</span>
        </>
      ) : (
        <>
          <Avatar className="size-6 rounded-sm">
            <AvatarFallback className="rounded-sm">S</AvatarFallback>
          </Avatar>
          <span className="hidden lg:flex">Select a server</span>
        </>
      )}
      <ChevronsUpDownIcon size={5} />
    </Button>
  );

  return (
    <div className="flex items-center">
      <ServerSelect
        value={selected}
        onValueChangeAdvanced={handleServerChange}
        trigger={trigger}
        open={open}
        onOpenChange={setOpen}
        footer={footer}
        showIp={false}
      />
    </div>
  );
}
