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
          url: `/servers/${ipAddress.server_id}/network/ips/${ipAddress.id}/primary`,
        })
      }
    >
      Set as primary
    </DropdownMenuItem>
  );
}
