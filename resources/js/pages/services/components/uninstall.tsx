import { Service } from '@/types/service';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { useDialog } from '@/hooks/use-dialog';

export default function Uninstall({ service }: { service: Service }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      variant="destructive"
      onSelect={() =>
        dialog.confirm.open({
          title: 'Uninstall service',
          description: 'Are you sure you want to uninstall this service? This action cannot be undone.',
          variant: 'destructive',
          confirmLabel: 'Uninstall',
          method: 'delete',
          url: `/servers/${service.server_id}/services/${service.id}`,
        })
      }
    >
      Uninstall
    </DropdownMenuItem>
  );
}
