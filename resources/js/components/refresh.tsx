import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { ChevronDownIcon, RefreshCwIcon } from 'lucide-react';
import { useEffect, useState } from 'react';
import { router } from '@inertiajs/react';

export default function Refresh() {
  const [poll, setPoll] = useState<{
    stop: VoidFunction;
    start: VoidFunction;
  }>();
  const [polling, setPolling] = useState(false);
  const storedInterval = (localStorage.getItem('refresh_interval') as '5' | '10' | '30' | '60' | '0') || '10';
  const [refreshInterval, setRefreshInterval] = useState<5 | 10 | 30 | 60 | 0>(
    storedInterval === '0' ? 0 : (parseInt(storedInterval) as 5 | 10 | 30 | 60),
  );
  const intervalLabels = {
    5: '5s',
    10: '10s',
    30: '30s',
    60: '1m',
    0: 'OFF',
  };

  const refresh = () => {
    router.reload({
      onStart: () => {
        setPolling(true);
      },
      onFinish: () => {
        setPolling(false);
      },
    });
  };

  useEffect(() => {
    poll?.stop();
    if (refreshInterval > 0) {
      setPoll(router.poll(refreshInterval * 1000));
    } else {
      poll?.stop();
      setPoll(undefined);
    }
    localStorage.setItem('refresh_interval', refreshInterval.toString());
  }, [refreshInterval]);

  return (
    <div className="flex items-center">
      <Button variant="outline" size="sm" className="md:rounded-r-none" onClick={refresh} disabled={polling}>
        {polling ? <RefreshCwIcon className="animate-spin" /> : <RefreshCwIcon />}
      </Button>
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button variant="outline" size="sm" className="hidden rounded-l-none border-l-0 md:flex">
            {intervalLabels[refreshInterval] || 'Unknown'}
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
