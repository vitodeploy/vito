import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { Database } from '@/types/database';
import { useDialog } from '@/hooks/use-dialog';

export default function Delete({ database }: { database: Database }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      variant="destructive"
      onSelect={() =>
        dialog.confirm.open({
          title: `Delete database [${database.name}]`,
          description: `Are you sure you want to delete database ${database.name}? This action cannot be undone.`,
          variant: 'destructive',
          confirmLabel: 'Delete',
          method: 'delete',
          url: route('databases.destroy', { server: database.server_id, database: database }),
        })
      }
    >
      Delete
    </DropdownMenuItem>
  );
}
