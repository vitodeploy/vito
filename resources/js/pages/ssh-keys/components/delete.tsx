import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { SshKey } from '@/types/ssh-key';
import { useDialog } from '@/hooks/use-dialog';

export default function Delete({ sshKey }: { sshKey: SshKey }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      variant="destructive"
      onSelect={() =>
        dialog.confirm.open({
          title: `Delete ${sshKey.name}`,
          description: 'Are you sure you want to delete this key?',
          variant: 'destructive',
          confirmLabel: 'Delete',
          method: 'delete',
          url: route('ssh-keys.destroy', sshKey.id),
        })
      }
    >
      Delete
    </DropdownMenuItem>
  );
}
