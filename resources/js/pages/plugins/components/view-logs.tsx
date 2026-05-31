import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { Plugin } from '@/types/plugin';
import { useDialog } from '@/hooks/use-dialog';

export default function ViewLogs({ plugin }: { plugin: Plugin }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem onSelect={() => dialog.pluginLogs.open({ name: plugin.name ?? plugin.folder, errors: plugin.errors })}>
      View Logs
    </DropdownMenuItem>
  );
}
