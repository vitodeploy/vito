import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { StorageProvider } from '@/types/storage-provider';
import { useDialog } from '@/hooks/use-dialog';

export default function Edit({ storageProvider }: { storageProvider: StorageProvider }) {
  const dialog = useDialog();

  return <DropdownMenuItem onSelect={() => dialog.storageProviderEdit.open({ storageProvider })}>Edit</DropdownMenuItem>;
}
