import { useQuery } from '@tanstack/react-query';
import { MetricsFilter, MetricsResponse } from '@/types/metric';
import { Server } from '@/types/server';

export function useMetrics(server: Server, filter?: MetricsFilter) {
  const resolved: MetricsFilter = filter ?? { period: '10m' };

  return useQuery<MetricsResponse>({
    queryKey: ['metrics', server.id, resolved.period, resolved.from, resolved.to],
    queryFn: async () => {
      const response = await fetch(route('monitoring.json', { server: server.id, ...resolved }));
      if (!response.ok) {
        throw new Error('Failed to fetch metrics');
      }
      return response.json();
    },
    refetchInterval: 60000,
    retry: false,
  });
}
