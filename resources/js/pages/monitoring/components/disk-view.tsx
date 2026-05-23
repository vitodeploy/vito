import { Server } from '@/types/server';
import { MetricsFilter } from '@/types/metric';
import { Skeleton } from '@/components/ui/skeleton';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { ResourceUsageChart } from '@/pages/monitoring/components/resource-usage-chart';
import { useMetrics } from '@/pages/monitoring/components/use-metrics';
import { cn, mbToGb } from '@/lib/utils';

export default function DiskView({ server, filter }: { server: Server; filter?: MetricsFilter }) {
  const query = useMetrics(server, filter);

  if (query.isLoading || !query.data) {
    return (
      <div className="flex flex-col gap-6">
        <Skeleton className="h-[140px] w-full rounded-xl border" />
        <Skeleton className="h-[260px] w-full rounded-xl border" />
        <Skeleton className="h-[260px] w-full rounded-xl border" />
      </div>
    );
  }

  const { current, history } = query.data;
  const latest = history.length > 0 ? history[history.length - 1] : null;
  const usedPercent = current?.disk_used_percent ?? 0;
  const usedAccent = usedPercent >= 90 ? 'destructive' : usedPercent >= 75 ? 'warning' : 'default';

  return (
    <div className="flex flex-col gap-6">
      {current?.disk_used_percent != null && (
        <Card>
          <CardHeader>
            <CardTitle>Current Capacity</CardTitle>
          </CardHeader>
          <CardContent className="flex flex-col gap-3 p-4">
            <div className="flex items-baseline justify-between">
              <span className="text-3xl font-semibold tabular-nums">{current.disk_used_percent.toFixed(2)}%</span>
              <span className="text-muted-foreground text-sm">{latest ? `${mbToGb(latest.disk_used)} / ${mbToGb(latest.disk_total)} GB` : ''}</span>
            </div>
            <div className="bg-muted relative h-3 w-full overflow-hidden rounded-full">
              <div
                className={cn(
                  'h-full rounded-full transition-all',
                  usedAccent === 'destructive' ? 'bg-destructive' : usedAccent === 'warning' ? 'bg-amber-500' : 'bg-emerald-500',
                )}
                style={{ width: `${Math.min(100, current.disk_used_percent)}%` }}
              />
            </div>
            <div className="text-muted-foreground flex justify-between text-xs">
              <span>{latest ? `${mbToGb(latest.disk_used)} GB used` : ''}</span>
              <span>{latest ? `${mbToGb(latest.disk_free)} GB free` : ''}</span>
            </div>
          </CardContent>
        </Card>
      )}

      <ResourceUsageChart
        title="Disk Usage %"
        label="Disk usage"
        dataKey="disk_used_percent"
        color="var(--color-chart-3)"
        chartData={history}
        height="medium"
        showXAxis
        valueFormatter={(v) => `${Number(v).toFixed(2)}%`}
      />

      <ResourceUsageChart
        title="Disk Used"
        label="Disk used"
        dataKey="disk_used"
        color="var(--color-chart-5)"
        chartData={history}
        height="medium"
        showXAxis
        valueFormatter={(v) => `${mbToGb(v as number)} GB`}
        formatter={(v) => `${mbToGb(v as number)} GB`}
      />
    </div>
  );
}
