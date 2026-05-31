import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { Plugin } from '@/types/plugin';
import { useDialog } from '@/hooks/use-dialog';

export default function Disable({ plugin }: { plugin: Plugin }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      variant="destructive"
      onSelect={() =>
        dialog.confirm.open({
          title: 'Disable plugin',
          description: `Are you sure you want to disable the plugin ${plugin.name ?? plugin.folder}?`,
          variant: 'destructive',
          confirmLabel: 'Disable',
          method: 'patch',
          url: route('plugins.disable'),
          data: { id: plugin.id },
        })
      }
    >
      Disable
    </DropdownMenuItem>
  );
}
