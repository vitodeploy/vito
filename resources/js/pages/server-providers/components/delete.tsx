import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { ServerProvider } from '@/types/server-provider';
import { useDialog } from '@/hooks/use-dialog';

export default function Delete({ serverProvider }: { serverProvider: ServerProvider }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      variant="destructive"
      onSelect={() =>
        dialog.confirm.open({
          title: `Delete ${serverProvider.name}`,
          description: `Are you sure you want to delete ${serverProvider.name}?`,
          variant: 'destructive',
          confirmLabel: 'Delete',
          method: 'delete',
          url: route('server-providers.destroy', serverProvider.id),
        })
      }
    >
      Delete
    </DropdownMenuItem>
  );
}
