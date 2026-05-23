import { Server } from '@/types/server';
import { CurrentMetric, MetricsFilter } from '@/types/metric';
import { StatTile } from '@/pages/monitoring/components/stat-tile';
import { useMetrics } from '@/pages/monitoring/components/use-metrics';
import { humanizeSeconds } from '@/lib/utils';

export default function SystemStatus({ server, filter }: { server: Server; filter?: MetricsFilter }) {
  const query = useMetrics(server, filter);
  const current: CurrentMetric | null = query.data?.current ?? null;
  const history = query.data?.history ?? [];
  const latestOom = history.length > 0 ? history[history.length - 1].oom_kill_count : null;

  if (!current) return null;

  return (
    <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
      <StatTile label="Uptime" value={humanizeSeconds(current.uptime_seconds)} />
      <StatTile label="Reboot Required" value={current.reboot_required ? 'Yes' : 'No'} accent={current.reboot_required ? 'destructive' : 'default'} />
      <StatTile label="OOM Kills" subtitle="Since last boot" value={latestOom ?? 0} accent={(latestOom ?? 0) > 0 ? 'warning' : 'default'} />
      <StatTile
        label="Cores"
        value={current.cpu_cores ?? 'N/A'}
        subtitle={current.cpu_physical_cores != null ? `${current.cpu_cores} logical / ${current.cpu_physical_cores} physical` : undefined}
      />
    </div>
  );
}
