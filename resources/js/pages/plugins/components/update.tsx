import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { Plugin } from '@/types/plugin';
import { useDialog } from '@/hooks/use-dialog';

export default function UpdatePlugin({ plugin }: { plugin: Plugin }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      onSelect={() =>
        dialog.confirm.open({
          title: 'Update plugin',
          description: `Are you sure you want to update the plugin ${plugin.name ?? plugin.folder} to the latest released version?`,
          confirmLabel: 'Update',
          method: 'patch',
          url: route('plugins.update'),
          data: { id: plugin.id },
        })
      }
    >
      Update
    </DropdownMenuItem>
  );
}
