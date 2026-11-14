import { Service } from '@/types/service';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { useDialog } from '@/hooks/use-dialog';

export function Action({ type, service }: { type: 'start' | 'stop' | 'restart' | 'reload' | 'enable' | 'disable'; service: Service }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      className="capitalize"
      onSelect={() =>
        dialog.confirm.open({
          title: `${type} service`,
          description: `Are you sure you want to ${type} the service?`,
          variant: ['disable', 'stop'].includes(type) ? 'destructive' : 'default',
          confirmLabel: type,
          method: 'post',
          url: `/servers/${service.server_id}/services/${service.id}/${type}`,
        })
      }
    >
      {type}
    </DropdownMenuItem>
  );
}
