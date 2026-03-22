import { SidebarTrigger } from '@/components/ui/sidebar';
import { ProjectSwitch } from '@/components/project-switch';
import { HeartIcon, SlashIcon, WifiIcon, WifiOffIcon } from 'lucide-react';
import { ServerSwitch } from '@/components/server-switch';
import AppCommand from '@/components/app-command';
import { SiteSwitch } from '@/components/site-switch';
import { usePage } from '@inertiajs/react';
import { SharedData } from '@/types';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { type SocketStatus } from '@/hooks/use-socket-events';

export function AppHeader({ socketStatus, socketReconnect }: { socketStatus: SocketStatus; socketReconnect: () => void }) {
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
        {socketStatus !== 'connected' && (
          <Tooltip>
            <TooltipTrigger asChild>
              <Button
                variant="outline"
                size="icon"
                className="size-8"
                onClick={socketReconnect}
                disabled={socketStatus === 'connecting'}
                aria-label={socketStatus === 'connecting' ? 'Connecting to WebSocket' : 'WebSocket disconnected, click to reconnect'}
              >
                {socketStatus === 'connecting' ? <WifiIcon className="size-4 animate-pulse" /> : <WifiOffIcon className="size-4" />}
              </Button>
            </TooltipTrigger>
            <TooltipContent>
              {socketStatus === 'connecting' ? 'Connecting to WebSocket...' : 'WebSocket connection failed. Click to retry.'}
            </TooltipContent>
          </Tooltip>
        )}
        <Button variant="outline" size="sm" onClick={() => window.open('https://github.com/sponsors/saeedvaziry')}>
          <HeartIcon className="text-pink-600" />
          <span className="hidden lg:block">Sponsor</span>
        </Button>
        <AppCommand />
      </div>
    </header>
  );
}
