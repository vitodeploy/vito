import { Service } from '@/types/service';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { useDialog } from '@/hooks/use-dialog';

export default function Networking({ service }: { service: Service }) {
  const dialog = useDialog();

  return <DropdownMenuItem onSelect={() => dialog.serviceNetworking.open({ service })}>Networking</DropdownMenuItem>;
}
