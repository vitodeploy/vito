import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { ServerIpAddress } from '@/types/server-ip';
import { useDialog } from '@/hooks/use-dialog';

export default function Delete({ ipAddress }: { ipAddress: ServerIpAddress }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      variant="destructive"
      onSelect={() =>
        dialog.confirm.open({
          title: `Delete IP [${ipAddress.ip}]`,
          description: `Are you sure you want to remove ${ipAddress.ip} from this server? This action cannot be undone.`,
          variant: 'destructive',
          confirmLabel: 'Delete',
          method: 'delete',
          url: route('servers.network.ips.destroy', { server: ipAddress.server_id, serverIpAddress: ipAddress.id }),
        })
      }
    >
      Delete
    </DropdownMenuItem>
  );
}
