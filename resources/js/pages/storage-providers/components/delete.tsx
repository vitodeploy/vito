import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { StorageProvider } from '@/types/storage-provider';
import { useDialog } from '@/hooks/use-dialog';

export default function Delete({ storageProvider }: { storageProvider: StorageProvider }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      variant="destructive"
      onSelect={() =>
        dialog.confirm.open({
          title: `Delete ${storageProvider.name}`,
          description: `Are you sure you want to delete ${storageProvider.name}?`,
          variant: 'destructive',
          confirmLabel: 'Delete',
          method: 'delete',
          url: route('storage-providers.destroy', storageProvider.id),
        })
      }
    >
      Delete
    </DropdownMenuItem>
  );
}
