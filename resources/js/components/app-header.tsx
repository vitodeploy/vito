import { SidebarTrigger } from '@/components/ui/sidebar';
import { ProjectSwitch } from '@/components/project-switch';
import { SlashIcon } from 'lucide-react';
import { ServerSwitch } from '@/components/server-switch';
import AppCommand from '@/components/app-command';
import { SiteSwitch } from '@/components/site-switch';
import { usePage } from '@inertiajs/react';
import { SharedData } from '@/types';
import Refresh from '@/components/refresh';

export function AppHeader() {
  const page = usePage<SharedData>();

  return (
    <header className="bg-background -ml-1 flex h-12 shrink-0 items-center justify-between gap-2 border-b p-4 md:-ml-2">
      <div className="flex items-center">
        <SidebarTrigger className="-ml-1 md:hidden" />
        <div className="flex items-center space-x-2 text-xs">
          <ProjectSwitch />
          <SlashIcon className="size-3" />
          <ServerSwitch />
          {page.props.server && page.props.server.services['webserver'] && (
            <>
              <SlashIcon className="size-3" />
              <SiteSwitch />
            </>
          )}
        </div>
      </div>
      <div className="flex items-center gap-2">
        <AppCommand />
        <Refresh />
      </div>
    </header>
  );
}
