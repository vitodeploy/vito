import { Server } from '@/types/server';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon, MoreVerticalIcon } from 'lucide-react';
import { useForm } from '@inertiajs/react';
import { useDialog } from '@/hooks/use-dialog';

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
  const dialog = useDialog();

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
        <DropdownMenuItem
          onSelect={() =>
            dialog.confirm.open({
              title: `Restart ${server.name}?`,
              description:
                'Are you sure you want to restart this server? Sites and services hosted on this server will be unavailable while it restarts. Connections in flight will be dropped.',
              variant: 'destructive',
              confirmLabel: 'Restart',
              method: 'post',
              url: route('servers.reboot', server.id),
            })
          }
        >
          Restart
        </DropdownMenuItem>
        <CheckForUpdates server={server} />
        <DropdownMenuItem
          disabled={server.updates == 0}
          onSelect={() =>
            dialog.confirm.open({
              title: `Update ${server.name}?`,
              description: `Apply ${server.updates} pending OS package ${server.updates === 1 ? 'update' : 'updates'} to this server? The upgrade can take several minutes and may briefly restart affected services. A server restart may be required afterwards.`,
              variant: 'destructive',
              confirmLabel: 'Update',
              method: 'post',
              url: route('servers.update', server.id),
            })
          }
        >
          Update
        </DropdownMenuItem>
        <DropdownMenuItem
          disabled={server.kernel_updates == 0}
          onSelect={() =>
            dialog.confirm.open({
              title: `Update kernel on ${server.name}?`,
              description: `This installs the pending kernel ${server.kernel_updates === 1 ? 'package' : 'packages'} (a full upgrade that may install or remove packages), then restarts the server to boot the new kernel. The server will be unavailable for a minute or two and connections in flight will be dropped.`,
              variant: 'destructive',
              confirmLabel: 'Update & restart',
              method: 'post',
              url: route('servers.update-kernel', server.id),
            })
          }
        >
          Update kernel
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
