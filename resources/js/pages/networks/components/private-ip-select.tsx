import { useState } from 'react';
import { router } from '@inertiajs/react';
import { RefreshCw, TriangleAlertIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { NetworkServerOption } from '@/types/network';

export type PrivateIp = NetworkServerOption['private_ips'][number];

type RefreshSource = {
  only: string[];
  resolve: (props: Record<string, unknown>, serverId: number) => PrivateIp[] | undefined;
};

const defaultRefreshSource: RefreshSource = {
  only: ['servers', 'flash'],
  resolve: (props, serverId) => {
    const servers = props.servers;
    if (!Array.isArray(servers)) {
      return undefined;
    }
    return (servers as NetworkServerOption[]).find((s) => s.id === serverId)?.private_ips;
  },
};

export default function PrivateIpSelect({
  serverId,
  ips,
  value,
  onValueChange,
  onRefreshed,
  disabled = false,
  refreshSource = defaultRefreshSource,
}: {
  serverId: number | null;
  ips: PrivateIp[];
  value?: number;
  onValueChange: (id: number) => void;
  onRefreshed: (serverId: number, ips: PrivateIp[]) => void;
  disabled?: boolean;
  refreshSource?: RefreshSource;
}) {
  const [refreshing, setRefreshing] = useState(false);

  const refresh = () => {
    if (!serverId) {
      return;
    }
    setRefreshing(true);
    router.post(
      route('servers.network.refresh', { server: serverId }),
      {},
      {
        preserveScroll: true,
        preserveState: true,
        only: refreshSource.only,
        onSuccess: (page) => {
          const refreshed = refreshSource.resolve(page.props as Record<string, unknown>, serverId);
          if (refreshed) {
            onRefreshed(serverId, refreshed);
          }
        },
        onFinish: () => setRefreshing(false),
      },
    );
  };

  const primaryChosen = ips.find((ip) => ip.id === value)?.is_primary;

  return (
    <div className="flex flex-col gap-2">
      <div className="flex items-center gap-2">
        <Select value={value ? String(value) : ''} onValueChange={(v) => onValueChange(Number(v))} disabled={disabled || !serverId}>
          <SelectTrigger className="flex-1">
            <SelectValue placeholder={serverId ? 'Select a private IP' : 'Select a server first'} />
          </SelectTrigger>
          <SelectContent>
            {ips.length === 0 ? (
              <div className="text-muted-foreground px-2 py-1.5 text-sm">No private IPs available</div>
            ) : (
              <SelectGroup>
                {ips.map((ip) => (
                  <SelectItem key={ip.id} value={String(ip.id)}>
                    {ip.ip}
                    {ip.is_primary ? ' (primary)' : ''}
                  </SelectItem>
                ))}
              </SelectGroup>
            )}
          </SelectContent>
        </Select>
        <Button variant="outline" type="button" aria-label="Refresh" disabled={disabled || refreshing || !serverId} onClick={refresh}>
          <RefreshCw className={refreshing ? 'animate-spin' : ''} />
        </Button>
      </div>
      {primaryChosen && (
        <Alert>
          <TriangleAlertIcon />
          <AlertDescription>This is the server&apos;s primary IP. Make sure you intend to use it for this network.</AlertDescription>
        </Alert>
      )}
    </div>
  );
}
