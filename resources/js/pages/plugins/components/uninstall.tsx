import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { Plugin } from '@/types/plugin';
import { useDialog } from '@/hooks/use-dialog';

export default function Uninstall({ plugin }: { plugin: Plugin }) {
  const dialog = useDialog();
  const label = plugin.is_installed ? 'Uninstall' : 'Remove';

  return (
    <DropdownMenuItem
      variant="destructive"
      onSelect={() =>
        dialog.confirm.open({
          title: `${label} Plugin`,
          description: `Are you sure you want to ${label.toLowerCase()} the plugin ${plugin.name ?? plugin.folder}?`,
          variant: 'destructive',
          confirmLabel: label,
          method: 'delete',
          url: route('plugins.uninstall'),
          data: { id: plugin.id },
        })
      }
    >
      {label}
    </DropdownMenuItem>
  );
}
