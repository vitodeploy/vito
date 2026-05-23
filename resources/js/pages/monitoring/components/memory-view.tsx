import { Server } from '@/types/server';
import { MetricsFilter } from '@/types/metric';
import { Skeleton } from '@/components/ui/skeleton';
import { StatTile } from '@/pages/monitoring/components/stat-tile';
import { ResourceUsageChart } from '@/pages/monitoring/components/resource-usage-chart';
import { useMetrics } from '@/pages/monitoring/components/use-metrics';
import { kbToGb } from '@/lib/utils';

export default function MemoryView({ server, filter }: { server: Server; filter?: MetricsFilter }) {
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
  const latest = history.length > 0 ? history[history.length - 1] : null;
  const latestOom = latest?.oom_kill_count ?? 0;
  const swapPresent = history.some((row) => (row.swap_total ?? 0) > 0);

  return (
    <div className="flex flex-col gap-6">
      <div className="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <StatTile
          label="Memory Usage"
          value={current?.memory_used_percent != null ? `${current.memory_used_percent.toFixed(2)}%` : 'N/A'}
          subtitle={latest ? `${kbToGb(latest.memory_used)} / ${kbToGb(latest.memory_total)} GB` : undefined}
        />
        <StatTile label="Memory Free" value={latest ? `${kbToGb(latest.memory_free)} GB` : 'N/A'} />
        <StatTile
          label="Swap Usage"
          value={current?.swap_used_percent != null ? `${current.swap_used_percent.toFixed(2)}%` : 'N/A'}
          subtitle={swapPresent ? undefined : 'No swap configured'}
        />
        <StatTile label="OOM Kills" subtitle="Since last boot" value={latestOom} accent={latestOom > 0 ? 'destructive' : 'default'} />
      </div>

      <ResourceUsageChart
        title="Memory Usage %"
        label="Memory usage"
        dataKey="memory_used_percent"
        color="var(--color-chart-2)"
        chartData={history}
        height="medium"
        showXAxis
        valueFormatter={(v) => `${Number(v).toFixed(2)}%`}
      />

      <ResourceUsageChart
        title="Memory Used"
        label="Memory used"
        dataKey="memory_used"
        color="var(--color-chart-3)"
        chartData={history}
        height="medium"
        showXAxis
        valueFormatter={(v) => `${kbToGb(v as number)} GB`}
        formatter={(v) => `${kbToGb(v as number)} GB`}
      />

      {swapPresent && (
        <ResourceUsageChart
          title="Swap Usage %"
          label="Swap"
          dataKey="swap_used_percent"
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
