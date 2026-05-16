import DateTime from '@/components/date-time';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { PaginatedData } from '@/types';
import { Server } from '@/types/server';
import { ServerLog } from '@/types/server-log';
import { useQuery } from '@tanstack/react-query';
import { ChevronRightIcon, LogsIcon, RefreshCwIcon, XIcon } from 'lucide-react';
import { ReactNode, useCallback, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';
import { useSocketListener } from '@/hooks/use-socket-events';

function InstantLogContent({ serverId, logId }: { serverId: number; logId: number }) {
  const [content, setContent] = useState('Loading...');
  const logIdRef = useRef(logId);
  logIdRef.current = logId;

  useEffect(() => {
    fetch(route('logs.show', { server: serverId, log: logId }))
      .then((response) => {
        if (!response.ok) {
          toast.error('Failed to fetch log');
          return;
        }
        return response.text();
      })
      .then((text) => {
        if (text) setContent(text);
      });
  }, [serverId, logId]);

  useSocketListener(
    useCallback((event) => {
      if (event.type !== 'server-log.content') return;
      if (!event.data || (event.data as { id?: number }).id !== logIdRef.current) return;
      const buf = (event.data as { content?: string }).content;
      if (buf) {
        setContent((prev) => prev + buf);
      }
    }, []),
  );

  return <div className="bg-muted/50 max-h-64 overflow-auto border-b px-4 py-2 font-mono text-xs whitespace-pre-wrap">{content}</div>;
}

export function InstantLogs({ server, children }: { server: Server; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const [selectedLogId, setSelectedLogId] = useState<number | null>(null);
  const [page, setPage] = useState(1);
  const [logs, setLogs] = useState<ServerLog[]>([]);

  const query = useQuery<PaginatedData<ServerLog>>({
    queryKey: ['instant-logs', server.id, page],
    queryFn: async () => {
      const response = await fetch(route('logs.json', { server: server.id, count: 15, page: page }));
      if (!response.ok) {
        toast.error('Failed to fetch logs');
        throw new Error('Network response was not ok');
      }
      const data = (await response.json()) as PaginatedData<ServerLog>;
      if (page === 1) {
        setLogs(data.data);
      } else {
        setLogs((prev) => [...new Set([...prev, ...data.data])]);
      }
      setPage(data.meta.current_page);
      return data;
    },
    enabled: false,
  });

  const refetch = query.refetch;

  useEffect(() => {
    if (open) {
      refetch();
    }
  }, [open, page, refetch]);

  const toggleLog = (logId: number) => {
    setSelectedLogId((prev) => (prev === logId ? null : logId));
  };

  const loadMore = () => {
    setPage((prev) => prev + 1);
  };

  const reset = () => {
    setLogs([]);
    setSelectedLogId(null);
    setPage(1);
  };

  useEffect(() => {
    const handleKeydown = (event: KeyboardEvent) => {
      if (event.ctrlKey && event.shiftKey && event.key === 'L') {
        event.preventDefault();
        setOpen(!open);
      }
    };

    document.addEventListener('keydown', handleKeydown);
    return () => document.removeEventListener('keydown', handleKeydown);
  }, [open, setOpen]);

  return (
    <Sheet open={open} onOpenChange={setOpen}>
      <SheetTrigger asChild>{children}</SheetTrigger>
      <SheetContent side="bottom" className="h-3/4" showClose={false}>
        <SheetHeader className="bg-muted/50 flex flex-row items-center justify-between border-b px-4 py-2">
          <div className="flex items-center gap-2">
            <LogsIcon className="h-4 w-4" />
            <SheetTitle className="text-sm font-medium">Logs - {server.name}</SheetTitle>
            <SheetDescription className="sr-only">Logs</SheetDescription>
          </div>
          <div className="flex items-center gap-2">
            <Tooltip delayDuration={0}>
              <TooltipTrigger asChild>
                <Button variant="ghost" size="icon" className="h-7 w-7" onClick={reset}>
                  {query.isFetching ? <RefreshCwIcon className="h-3 w-3 animate-spin" /> : <RefreshCwIcon className="h-3 w-3" />}
                </Button>
              </TooltipTrigger>
              <TooltipContent>Reload</TooltipContent>
            </Tooltip>
            <Tooltip delayDuration={0}>
              <TooltipTrigger asChild>
                <Button variant="ghost" size="icon" className="h-7 w-7" onClick={() => setOpen(false)}>
                  <XIcon className="h-3 w-3" />
                </Button>
              </TooltipTrigger>
              <TooltipContent>Close</TooltipContent>
            </Tooltip>
          </div>
        </SheetHeader>
        <div className="flex h-full flex-col overflow-y-auto">
          {logs.map((log, index) => (
            <div key={`log-${log.id}`}>
              <Button
                variant="ghost"
                className="flex w-full items-center justify-between rounded-none border-b px-4 py-2 font-mono text-xs"
                onClick={() => toggleLog(log.id)}
                tabIndex={index + 1}
                autoFocus={index === 0}
              >
                <div className="flex items-center gap-2">
                  <ChevronRightIcon className="size-4" />
                  <div>{log.name}</div>
                </div>
                <div className="text-muted-foreground text-xs">
                  <DateTime date={log.created_at} />
                </div>
              </Button>
              {selectedLogId === log.id && <InstantLogContent serverId={server.id} logId={log.id} />}
            </div>
          ))}
          <Button variant="ghost" onClick={loadMore} tabIndex={logs.length + 1}>
            {query.isFetching ? 'Loading...' : 'Load More'}
          </Button>
        </div>
      </SheetContent>
    </Sheet>
  );
}
