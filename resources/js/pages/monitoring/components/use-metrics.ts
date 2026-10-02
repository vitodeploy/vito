import { useQuery } from '@tanstack/react-query';
import { MetricsFilter, MetricsResponse } from '@/types/metric';
import { Server } from '@/types/server';

const REFETCH_INTERVALS: Record<string, number> = {
  '10m': 60_000,
  '30m': 60_000,
  '1h': 300_000,
  '12h': 300_000,
  '1d': 600_000,
  '7d': 600_000,
  custom: 600_000,
};

export function useMetrics(server: Server, filter?: MetricsFilter) {
  const resolved: MetricsFilter = filter ?? { period: '10m' };
  const refetchInterval = REFETCH_INTERVALS[resolved.period] ?? 60_000;

  return useQuery<MetricsResponse>({
    queryKey: ['metrics', server.id, resolved.period, resolved.from, resolved.to],
    queryFn: async () => {
      const params = new URLSearchParams();
      for (const [key, value] of Object.entries(resolved)) {
        if (value != null) params.set(key, String(value));
      }
      const response = await fetch(`/servers/${server.id}/monitoring/json?${params}`);
      if (!response.ok) {
        throw new Error('Failed to fetch metrics');
      }
      return response.json();
    },
    refetchInterval,
    retry: false,
  });
}
