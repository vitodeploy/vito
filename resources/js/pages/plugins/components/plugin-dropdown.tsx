import { Plugin } from '@/types/plugin';
import { DropdownMenu, DropdownMenuContent, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { MoreVerticalIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import DisablePlugin from '@/pages/plugins/components/disable';
import UpdatePlugin from '@/pages/plugins/components/update';
import EnablePlugin from '@/pages/plugins/components/enable';
import Uninstall from '@/pages/plugins/components/uninstall';
import ViewLogs from '@/pages/plugins/components/view-logs';
import DeleteLogs from '@/pages/plugins/components/delete-logs';
import InstallPlugin from '@/pages/plugins/components/install';

export default function PluginDropdown({ plugin }: { plugin: Plugin }) {
  return (
    <DropdownMenu modal={false}>
      <DropdownMenuTrigger asChild>
        <Button variant="outline" className="w-8 p-0">
          <span className="sr-only">Open menu</span>
          <MoreVerticalIcon />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end">
        {!plugin.is_installed && <InstallPlugin plugin={plugin} />}
        {plugin.updates_available && <UpdatePlugin plugin={plugin} />}
        {plugin.is_enabled && <DisablePlugin plugin={plugin} />}
        {!plugin.is_enabled && plugin.is_installed && <EnablePlugin plugin={plugin} />}
        {!plugin.is_enabled && <Uninstall plugin={plugin} />}
        <DropdownMenuSeparator />
        <ViewLogs plugin={plugin} />
        <DeleteLogs plugin={plugin} />
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
