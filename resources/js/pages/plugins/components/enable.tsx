import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { Plugin } from '@/types/plugin';
import { useDialog } from '@/hooks/use-dialog';

export default function EnablePlugin({ plugin }: { plugin: Plugin }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      onSelect={() =>
        dialog.confirm.open({
          title: 'Enable plugin',
          description: `Are you sure you want to enable the plugin ${plugin.name ?? plugin.folder}?`,
          confirmLabel: 'Enable',
          method: 'patch',
          url: route('plugins.enable'),
          data: { id: plugin.id },
        })
      }
    >
      Enable
    </DropdownMenuItem>
  );
}
