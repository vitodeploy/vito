import { useCallback } from 'react';
import { router } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { MoreVerticalIcon } from 'lucide-react';
import Port from '@/pages/site-settings/components/port';
import StartCommand from '@/pages/site-settings/components/start-command';
import { WorkerAction, WorkerEnvironment, WorkerLogs } from '@/pages/workers/components/worker-row-actions';
import ErrorIndicator from '@/components/error-indicator';
import { useRealtimeRecord, useSocketListener, type SocketEventData } from '@/hooks/use-socket-events';
import { useDialog } from '@/hooks/use-dialog';
import { Site } from '@/types/site';
import { Worker } from '@/types/worker';

export default function ProxiedAppCard({ site, initialWorker }: { site: Site; initialWorker: Worker | null }) {
  const dialog = useDialog();
  const worker = useRealtimeRecord<Worker>(initialWorker, 'worker');

  useSocketListener(
    useCallback(
      (event: SocketEventData) => {
        if (event.type !== 'site.updated') return;
        const data = event.data as { id?: number; bootstrap_worker_id?: number | null } | null;
        if (!data || data.id !== site.id) return;

        if (initialWorker === null && data.bootstrap_worker_id != null) {
          router.reload({ only: ['worker'] });
        }
      },
      [initialWorker, site.id],
    ),
  );

  return (
    <Card>
      <CardContent className="grid grid-cols-2 p-0 xl:grid-cols-[1fr_2fr_1fr]">
        <div className="col-start-1 row-start-1 flex min-w-0 items-center justify-between gap-4 border-r border-b p-4 xl:border-b-0">
          <span className="shrink-0 text-sm font-medium">Port</span>
          <Port site={site}>
            <Button variant="outline" className="h-6 font-mono text-xs">
              {site.port}
            </Button>
          </Port>
        </div>

        <div className="col-span-2 col-start-1 row-start-2 flex min-w-0 items-center gap-4 overflow-hidden p-4 xl:col-span-1 xl:col-start-2 xl:row-start-1 xl:border-r">
          <span className="shrink-0 text-sm font-medium">Start command</span>
          <div className="flex min-w-0 flex-1 justify-end overflow-hidden">
            <StartCommand site={site}>
              <Button variant="outline" className="h-6 max-w-full min-w-0 overflow-hidden font-mono text-xs">
                <span className="block min-w-0 truncate" title={site.start_command ?? undefined}>
                  {site.start_command ?? 'Not set'}
                </span>
              </Button>
            </StartCommand>
          </div>
        </div>

        <div className="col-start-2 row-start-1 flex min-w-0 items-center justify-between gap-4 border-b p-4 xl:col-start-3 xl:border-b-0">
          <span className="shrink-0 text-sm font-medium">Worker</span>
          <div className="flex items-center gap-1">
            <ErrorIndicator error={worker?.error ?? null} label="Worker error" />
            <Badge variant={worker?.status_color ?? 'gray'} className="text-xs">
              {worker?.status ?? 'pending_deploy'}
            </Badge>
            <DropdownMenu modal={false}>
              <DropdownMenuTrigger asChild>
                <Button variant="outline" className="h-6 w-6 p-0">
                  <span className="sr-only">Open worker menu</span>
                  <MoreVerticalIcon className="size-3.5" />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                {worker ? (
                  <>
                    <WorkerAction type="start" worker={worker} />
                    <WorkerAction type="stop" worker={worker} />
                    <WorkerAction type="restart" worker={worker} />
                    <WorkerLogs worker={worker} />
                    <WorkerEnvironment worker={worker} />
                  </>
                ) : (
                  <DropdownMenuItem onSelect={() => dialog.workerEnv.open({ serverId: site.server_id, siteId: site.id })}>
                    Environment
                  </DropdownMenuItem>
                )}
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
