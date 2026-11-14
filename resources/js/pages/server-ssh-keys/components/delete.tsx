import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { usePage } from '@inertiajs/react';
import { SshKey } from '@/types/ssh-key';
import { Server } from '@/types/server';
import { useDialog } from '@/hooks/use-dialog';

export default function Delete({ sshKey }: { sshKey: SshKey }) {
  const dialog = useDialog();
  const page = usePage<{
    server: Server;
  }>();

  return (
    <DropdownMenuItem
      variant="destructive"
      onSelect={() =>
        dialog.confirm.open({
          title: `Delete ${sshKey.name} from ${page.props.server.name}`,
          description: `Are you sure you want to delete this key from ${page.props.server.name}?`,
          variant: 'destructive',
          confirmLabel: 'Delete',
          method: 'delete',
          url: `/servers/${page.props.server.id}/ssh-keys/${sshKey.id}`,
        })
      }
    >
      Delete
    </DropdownMenuItem>
  );
}
