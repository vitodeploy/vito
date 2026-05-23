import { Server } from '@/types/server';
import { useQuery } from '@tanstack/react-query';
import { MetricsFilter, MetricsResponse } from '@/types/metric';
import { ResourceUsageChart } from '@/pages/monitoring/components/resource-usage-chart';
import { kbToGb, mbToGb } from '@/lib/utils';
import { Skeleton } from '@/components/ui/skeleton';

export default function MetricsCards({ server, filter, metric }: { server: Server; filter?: MetricsFilter; metric?: string }) {
  if (!filter) {
    filter = {
      period: '10m',
    };
  }

  const query = useQuery<MetricsResponse>({
    queryKey: ['metrics', server.id, filter.period, filter.from, filter.to],
    queryFn: async () => {
      const response = await fetch(route('monitoring.json', { server: server.id, ...filter }));
      if (!response.ok) {
        throw new Error('Failed to fetch metrics');
      }
      return response.json();
    },
    refetchInterval: 60000,
    retry: false,
  });

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
              dataKey="memory_used"
              color="var(--color-chart-2)"
              chartData={history}
              link={route('monitoring.show', { server: server.id, metric: 'memory' })}
              formatter={(value) => {
                return `${kbToGb(value as string)} GB`;
              }}
              single={metric !== undefined}
            />
          )}
          {(!metric || metric === 'disk') && (
            <ResourceUsageChart
              title="Disk Usage"
              label="Disk usage"
              dataKey="disk_used"
              color="var(--color-chart-3)"
              chartData={history}
              link={route('monitoring.show', { server: server.id, metric: 'disk' })}
              formatter={(value) => {
                return `${mbToGb(value as string)} GB`;
              }}
              single={metric !== undefined}
            />
          )}
        </>
      )}
    </div>
  );
}
