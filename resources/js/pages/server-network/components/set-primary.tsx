import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { ServerIpAddress } from '@/types/server-ip';
import { useDialog } from '@/hooks/use-dialog';

export default function SetPrimary({ ipAddress }: { ipAddress: ServerIpAddress }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      onSelect={() =>
        dialog.confirm.open({
          title: `Set primary IP [${ipAddress.ip}]`,
          description: `Make ${ipAddress.ip} the primary address for this server. Vito uses the primary public IP to connect to the server, so only change this if the address is reachable.`,
          confirmLabel: 'Set as primary',
          method: 'post',
          url: route('servers.network.ips.primary', { server: ipAddress.server_id, serverIpAddress: ipAddress.id }),
        })
      }
    >
      Set as primary
    </DropdownMenuItem>
  );
}
