import { Server } from '@/types/server';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon, MoreVerticalIcon } from 'lucide-react';
import RebootServer from '@/pages/servers/components/reboot-server';
import { useForm } from '@inertiajs/react';
import UpdateServer from '@/pages/servers/components/update-server';

function CheckForUpdates({ server }: { server: Server }) {
  const form = useForm();

  const submit = () => {
    form.post(route('servers.check-for-updates', server.id));
  };

  return (
    <DropdownMenuItem
      className="w-40"
      onSelect={(e) => {
        e.preventDefault();
        submit();
      }}
    >
      {form.processing && <LoaderCircleIcon className="size-4 animate-spin" />}
      Check for updates
    </DropdownMenuItem>
  );
}

function CheckConnection({ server }: { server: Server }) {
  const form = useForm();

  const submit = () => {
    form.patch(route('servers.status', server.id));
  };

  return (
    <DropdownMenuItem
      className="w-40"
      onSelect={(e) => {
        e.preventDefault();
        submit();
      }}
    >
      {form.processing && <LoaderCircleIcon className="size-4 animate-spin" />}
      Check connection
    </DropdownMenuItem>
  );
}

export default function ServerActions({ server }: { server: Server }) {
  return (
    <DropdownMenu modal={false}>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" className="h-8 w-8 p-0">
          <span className="sr-only">Open menu</span>
          <MoreVerticalIcon />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        <CheckConnection server={server} />
        <RebootServer server={server}>
          <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Reboot</DropdownMenuItem>
        </RebootServer>
        <CheckForUpdates server={server} />
        <UpdateServer server={server}>
          <DropdownMenuItem onSelect={(e) => e.preventDefault()} disabled={server.updates == 0}>
            Update
          </DropdownMenuItem>
        </UpdateServer>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
