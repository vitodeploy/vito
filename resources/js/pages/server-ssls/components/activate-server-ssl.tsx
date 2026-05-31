import { SSL } from '@/types/ssl';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { useDialog } from '@/hooks/use-dialog';

export default function ActivateServerSsl({ ssl }: { ssl: SSL }) {
  const dialog = useDialog();

  return <DropdownMenuItem onSelect={() => dialog.activateServerSsl.open({ serverId: ssl.server_id, sslId: ssl.id })}>Activate</DropdownMenuItem>;
}
