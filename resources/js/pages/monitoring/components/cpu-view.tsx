import { Server } from '@/types/server';
import { MetricsFilter } from '@/types/metric';
import { Skeleton } from '@/components/ui/skeleton';
import { StatTile } from '@/pages/monitoring/components/stat-tile';
import { ResourceUsageChart } from '@/pages/monitoring/components/resource-usage-chart';
import { useMetrics } from '@/pages/monitoring/components/use-metrics';

export default function CpuView({ server, filter }: { server: Server; filter?: MetricsFilter }) {
  const query = useMetrics(server, filter);

  if (query.isLoading || !query.data) {
    return (
      <div className="grid grid-cols-1 gap-4">
        <Skeleton className="h-[88px] w-full rounded-xl border" />
        <Skeleton className="h-[260px] w-full rounded-xl border" />
        <Skeleton className="h-[260px] w-full rounded-xl border" />
      </div>
    );
  }

  const { current, history } = query.data;
  const stealPeak = history.reduce((max, row) => Math.max(max, row.cpu_steal_percent ?? 0), 0);
  const showSteal = stealPeak > 0;

  return (
    <div className="flex flex-col gap-6">
      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <StatTile label="CPU Usage" value={current?.cpu_usage_percent != null ? `${current.cpu_usage_percent.toFixed(2)}%` : 'N/A'} />
        <StatTile
          label="Cores"
          value={current?.cpu_cores ?? 'N/A'}
          subtitle={current?.cpu_physical_cores != null ? `${current.cpu_cores} logical / ${current.cpu_physical_cores} physical` : undefined}
        />
        <StatTile label="Current Load" value={history.length > 0 ? history[history.length - 1].load.toFixed(2) : 'N/A'} subtitle="1-min average" />
        <StatTile
          label="CPU Steal (peak)"
          value={`${stealPeak.toFixed(2)}%`}
          subtitle="In selected period"
          accent={stealPeak > 1 ? 'warning' : 'default'}
        />
      </div>

      <ResourceUsageChart
        title="CPU Usage %"
        label="CPU usage"
        dataKey="cpu_usage_percent"
        color="var(--color-chart-1)"
        chartData={history}
        height="medium"
        showXAxis
        valueFormatter={(v) => `${Number(v).toFixed(2)}%`}
      />

      <ResourceUsageChart
        title="Load Average"
        label="Load"
        dataKey="load"
        color="var(--color-chart-2)"
        chartData={history}
        height="medium"
        showXAxis
        valueFormatter={(v) => Number(v).toFixed(2)}
      />

      {showSteal && (
        <ResourceUsageChart
          title="CPU Steal %"
          label="Steal"
          dataKey="cpu_steal_percent"
          color="var(--color-chart-4)"
          chartData={history}
          height="medium"
          showXAxis
          valueFormatter={(v) => `${Number(v).toFixed(2)}%`}
        />
      )}
    </div>
  );
}
