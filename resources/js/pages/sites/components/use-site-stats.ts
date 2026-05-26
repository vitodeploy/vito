import { useQuery } from '@tanstack/react-query';
import { SiteStatsResponse } from '@/types/site-stats';
import { Server } from '@/types/server';
import { Site } from '@/types/site';

export function useSiteStats(server: Server, site: Site, month?: string) {
  return useQuery<SiteStatsResponse>({
    queryKey: ['site-stats', site.id, month ?? 'current'],
    queryFn: async () => {
      const response = await fetch(route('site-stats.json', { server: server.id, site: site.id, month }));
      if (!response.ok) {
        throw new Error('Failed to fetch site statistics');
      }
      return response.json();
    },
    retry: false,
    refetchOnWindowFocus: false,
  });
}
