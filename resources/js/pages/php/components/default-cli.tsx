import { Service } from '@/types/service';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { useDialog } from '@/hooks/use-dialog';

export default function DefaultCli({ service }: { service: Service }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      disabled={service.is_default}
      onSelect={() =>
        dialog.confirm.open({
          title: 'Make default cli',
          description: `Are you sure you want to make PHP ${service.version} the default cli?`,
          confirmLabel: 'Save',
          method: 'post',
          url: `/servers/${service.server_id}/php/${service.id}/default-cli`,
          data: { version: service.version },
        })
      }
    >
      Make default cli
    </DropdownMenuItem>
  );
}
