import { Server } from '@/types/server';
import { CloudIcon, IdCardIcon, LoaderCircleIcon, MapPinIcon, SlashIcon } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import ServerStatus from '@/pages/servers/components/status';
import ServerActions from '@/pages/servers/components/actions';
import { cn } from '@/lib/utils';

export default function ServerHeader({ server }: { server: Server }) {
  return (
    <div className="flex items-center justify-between border-b px-4 py-2">
      <div className="space-y-2">
        <div className="flex items-center space-x-2 text-xs">
          <Tooltip>
            <TooltipTrigger asChild>
              <div className="flex items-center space-x-1">
                <IdCardIcon className="size-4" />
                <div className="hidden lg:inline-flex">{server.name}</div>
              </div>
            </TooltipTrigger>
            <TooltipContent>
              <span className="lg:hidden">{server.name}</span>
              <span className="hidden lg:inline-flex">Server Name</span>
            </TooltipContent>
          </Tooltip>
          <SlashIcon className="size-3" />
          <Tooltip>
            <TooltipTrigger asChild>
              <div className="flex items-center space-x-1">
                <CloudIcon className="size-4" />
                <div className="hidden lg:inline-flex">{server.provider}</div>
              </div>
            </TooltipTrigger>
            <TooltipContent>
              <div>
                <span className="lg:hidden">{server.provider}</span>
                <span className="hidden lg:inline-flex">Server Provider</span>
              </div>
            </TooltipContent>
          </Tooltip>
          <SlashIcon className="size-3" />
          <Tooltip>
            <TooltipTrigger asChild>
              <div className="flex items-center space-x-1">
                <MapPinIcon className="size-4" />
                <div className="hidden lg:inline-flex">{server.ip}</div>
              </div>
            </TooltipTrigger>
            <TooltipContent>
              <span className="lg:hidden">{server.ip}</span>
              <span className="hidden lg:inline-flex">Server IP</span>
            </TooltipContent>
          </Tooltip>
          {['installing', 'installation_failed'].includes(server.status) && (
            <>
              <SlashIcon className="size-3" />
              <Tooltip>
                <TooltipTrigger asChild>
                  <div className="flex items-center space-x-1">
                    <LoaderCircleIcon className={cn('size-4', server.status === 'installing' ? 'animate-spin' : '')} />
                    <div>%{parseInt(server.progress || '0')}</div>
                  </div>
                </TooltipTrigger>
                <TooltipContent>Installation Progress</TooltipContent>
              </Tooltip>
            </>
          )}
        </div>
      </div>
      <div className="flex items-center space-x-1">
        <ServerStatus server={server} />
        <ServerActions server={server} />
      </div>
    </div>
  );
}
