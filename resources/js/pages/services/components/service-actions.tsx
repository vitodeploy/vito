import { MoreVerticalIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Action } from '@/pages/services/components/action';
import { ResyncStats } from '@/pages/services/components/resync-stats';
import Uninstall from '@/pages/services/components/uninstall';
import Networking from '@/pages/services/components/networking';
import ConfigFile from '@/pages/services/components/config-file';
import InstallationLog from '@/pages/services/components/installation-log';
import { Service } from '@/types/service';

export default function ServiceActions({ service }: { service: Service }) {
  return (
    <div className="flex items-center justify-end">
      <DropdownMenu modal={false}>
        <DropdownMenuTrigger asChild>
          <Button variant="ghost" className="h-8 w-8 p-0">
            <span className="sr-only">Open menu</span>
            <MoreVerticalIcon />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <Action type="start" service={service} />
          <Action type="stop" service={service} />
          <Action type="restart" service={service} />
          <Action type="reload" service={service} />
          <Action type="enable" service={service} />
          <Action type="disable" service={service} />
          {service.supports_networking && (
            <>
              <DropdownMenuSeparator />
              <Networking service={service} />
            </>
          )}
          {service.type === 'log_analysis' && (
            <>
              <DropdownMenuSeparator />
              <ResyncStats service={service} />
            </>
          )}
          {service.config_paths && service.config_paths.length > 0 && (
            <>
              <DropdownMenuSeparator />
              {service.config_paths.map((configPath) => (
                <ConfigFile key={configPath.name} service={service} configPath={configPath} />
              ))}
            </>
          )}
          {service.log && (
            <>
              <DropdownMenuSeparator />
              <InstallationLog service={service} />
            </>
          )}
          <DropdownMenuSeparator />
          <Uninstall service={service} />
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  );
}
