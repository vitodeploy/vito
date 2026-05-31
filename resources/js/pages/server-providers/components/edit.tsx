import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { ServerProvider } from '@/types/server-provider';
import { useDialog } from '@/hooks/use-dialog';

export default function Edit({ serverProvider }: { serverProvider: ServerProvider }) {
  const dialog = useDialog();

  return <DropdownMenuItem onSelect={() => dialog.serverProviderEdit.open({ serverProvider })}>Edit</DropdownMenuItem>;
}
