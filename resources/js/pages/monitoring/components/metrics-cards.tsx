import { Server } from '@/types/server';
import { MetricsFilter } from '@/types/metric';
import { ResourceUsageChart } from '@/pages/monitoring/components/resource-usage-chart';
import { Skeleton } from '@/components/ui/skeleton';
import { useMetrics } from '@/pages/monitoring/components/use-metrics';

export default function MetricsCards({ server, filter, metric }: { server: Server; filter?: MetricsFilter; metric?: string }) {
  const query = useMetrics(server, filter);
  const history = query.data?.history ?? [];

  return (
    <div className={metric ? 'grid grid-cols-1 gap-4' : 'grid grid-cols-1 gap-6 lg:grid-cols-3'}>
      {query.isLoading && (
        <>
          {metric ? (
            <Skeleton className="h-[510px] w-full rounded-xl border shadow-xs" />
          ) : (
            <>
              <Skeleton className="h-[210px] w-full rounded-xl border shadow-xs" />
              <Skeleton className="h-[210px] w-full rounded-xl border shadow-xs" />
              <Skeleton className="h-[210px] w-full rounded-xl border shadow-xs" />
            </>
          )}
        </>
      )}
      {query.data && (
        <>
          {(!metric || metric === 'load') && (
            <ResourceUsageChart
              title="CPU Load"
              label="CPU load"
              dataKey="load"
              color="var(--color-chart-1)"
              chartData={history}
              link={route('monitoring.show', { server: server.id, metric: 'load' })}
              single={metric !== undefined}
            />
          )}
          {(!metric || metric === 'memory') && (
            <ResourceUsageChart
              title="Memory Usage"
              label="Memory usage"
              dataKey="memory_used_percent"
              color="var(--color-chart-2)"
              chartData={history}
              link={route('monitoring.show', { server: server.id, metric: 'memory' })}
              formatter={(value) => `${Number(value).toFixed(2)}%`}
              valueFormatter={(value) => `${Number(value).toFixed(2)}%`}
              single={metric !== undefined}
            />
          )}
          {(!metric || metric === 'disk') && (
            <ResourceUsageChart
              title="Disk Usage"
              label="Disk usage"
              dataKey="disk_used_percent"
              color="var(--color-chart-3)"
              chartData={history}
              link={route('monitoring.show', { server: server.id, metric: 'disk' })}
              formatter={(value) => `${Number(value).toFixed(2)}%`}
              valueFormatter={(value) => `${Number(value).toFixed(2)}%`}
              single={metric !== undefined}
            />
          )}
        </>
      )}
    </div>
  );
}
