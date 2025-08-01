import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { ChevronDownIcon, RefreshCwIcon } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { router } from '@inertiajs/react';
import { useInputFocus } from '@/stores/useInputFocus';
import { usePageActive } from '@/hooks/use-page-active';

export default function Refresh() {
  const readInitial = () => (typeof window !== 'undefined' && (localStorage.getItem('refresh_interval') as '5' | '10' | '30' | '60' | '0')) || '10';

  const [polling, setPolling] = useState(false);
  const [refreshInterval, setRefreshInterval] = useState<5 | 10 | 30 | 60 | 0>(
    readInitial() === '0' ? 0 : (parseInt(readInitial()) as 5 | 10 | 30 | 60),
  );

  const isFocused = useInputFocus((state) => state.isFocused);

  const timerRef = useRef<number | null>(null);
  const stoppedRef = useRef<boolean>(false);

  const isPageActive = usePageActive();

  const intervalLabels: Record<5 | 10 | 30 | 60 | 0, string> = {
    5: '5s',
    10: '10s',
    30: '30s',
    60: '1m',
    0: 'OFF',
  };

  const refresh = () => {
    router.reload({
      onStart: () => setPolling(true),
      onFinish: () => setPolling(false),
    });
  };

  useEffect(() => {
    // persist choice
    if (typeof window !== 'undefined') {
      localStorage.setItem('refresh_interval', String(refreshInterval));
    }

    // clear any existing timer
    if (timerRef.current) {
      window.clearTimeout(timerRef.current);
      timerRef.current = null;
    }
    stoppedRef.current = false;

    // no polling
    if (refreshInterval === 0 || !isPageActive) return;

    // self-rescheduling tick: schedule next only after current finishes
    const tick = () => {
      if (stoppedRef.current) return;

      if (isPageActive && !isFocused) {
        router.reload({
          onStart: () => setPolling(true),
          onFinish: () => {
            setPolling(false);
            if (!stoppedRef.current && refreshInterval > 0 && isPageActive) {
              timerRef.current = window.setTimeout(tick, refreshInterval * 1000);
            }
          },
        });
      }
    };

    // initial schedule
    timerRef.current = window.setTimeout(tick, refreshInterval * 1000);

    // cleanup on interval change/unmount
    return () => {
      stoppedRef.current = true;
      if (timerRef.current) {
        window.clearTimeout(timerRef.current);
        timerRef.current = null;
      }
    };
  }, [refreshInterval, isPageActive, isFocused]);

  return (
    <div className="flex items-center">
      <Button variant="outline" size="sm" className="md:rounded-r-none" onClick={refresh} disabled={polling}>
        {polling ? <RefreshCwIcon className="animate-spin" /> : <RefreshCwIcon />}
      </Button>
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button variant="outline" size="sm" className="hidden rounded-l-none border-l-0 md:flex">
            {intervalLabels[refreshInterval]}
            <ChevronDownIcon />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuItem onSelect={() => setRefreshInterval(5)}>5s</DropdownMenuItem>
          <DropdownMenuItem onSelect={() => setRefreshInterval(10)}>10s</DropdownMenuItem>
          <DropdownMenuItem onSelect={() => setRefreshInterval(30)}>30s</DropdownMenuItem>
          <DropdownMenuItem onSelect={() => setRefreshInterval(60)}>1m</DropdownMenuItem>
          <DropdownMenuItem onSelect={() => setRefreshInterval(0)}>OFF</DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  );
}
