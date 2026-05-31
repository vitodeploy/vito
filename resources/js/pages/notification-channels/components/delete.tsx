import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { NotificationChannel } from '@/types/notification-channel';
import { useDialog } from '@/hooks/use-dialog';

export default function Delete({ notificationChannel }: { notificationChannel: NotificationChannel }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      variant="destructive"
      onSelect={() =>
        dialog.confirm.open({
          title: `Delete ${notificationChannel.name}`,
          description: `Are you sure you want to delete ${notificationChannel.name}?`,
          variant: 'destructive',
          confirmLabel: 'Delete',
          method: 'delete',
          url: route('notification-channels.destroy', notificationChannel.id),
        })
      }
    >
      Delete
    </DropdownMenuItem>
  );
}
