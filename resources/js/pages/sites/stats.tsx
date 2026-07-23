import { Head, router, usePage } from '@inertiajs/react';
import { useCallback, useState } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { BookOpenIcon, RefreshCwIcon } from 'lucide-react';
import { toast } from 'sonner';
import { useSocketListener } from '@/hooks/use-socket-events';

import { Site } from '@/types/site';
import { Server } from '@/types/server';
import ServerLayout from '@/layouts/server/layout';
import SiteBanners from '@/components/site-banners';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Skeleton } from '@/components/ui/skeleton';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DataTable } from '@/components/data-table';
import { ColumnDef } from '@tanstack/react-table';
import { useSiteStats } from '@/pages/sites/components/use-site-stats';
import { StatsChart } from '@/pages/sites/components/stats-chart';
import { SiteStatsPanelRow, SiteStatsStatusCode } from '@/types/site-stats';
import { formatBytes } from '@/lib/utils';

type Page = {
  server: Server;
  site: Site;
  hasStatsService: boolean;
  statsEnabled: boolean;
};

function formatNumber(value: number): string {
  return value.toLocaleString();
}

function formatMonth(value: string): string {
  const [year, month] = value.split('-').map(Number);
  if (!year || !month) {
    return value;
  }
  return new Date(year, month - 1, 1).toLocaleDateString('en-US', { month: 'short', year: '2-digit' });
}

function formatDay(value: string): string {
  const parts = value.split('-');
  if (parts.length !== 3) {
    return value;
  }
  const day = parseInt(parts[2], 10);
  if (Number.isNaN(day)) {
    return value;
  }
  const j = day % 10;
  const k = day % 100;
  const suffix = j === 1 && k !== 11 ? 'st' : j === 2 && k !== 12 ? 'nd' : j === 3 && k !== 13 ? 'rd' : 'th';
  return `${day}${suffix}`;
}

export default function SiteStats() {
  const page = usePage<Page>();
  const { server, site, hasStatsService, statsEnabled } = page.props;

  return (
    <ServerLayout>
      <Head title={`Stats - ${site.domain} - ${server.name}`} />
      <Container className="max-w-5xl">
        {statsEnabled && hasStatsService ? (
          <StatsView server={server} site={site} />
        ) : (
          <>
            <HeaderContainer>
              <Heading title="Stats" description="Web statistics for this site" />
              <DocsButton />
            </HeaderContainer>
            <SiteBanners site={site} />
            <Card>
              <CardContent className="text-muted-foreground p-6 text-sm">
                {!statsEnabled ? (
                  <>
                    Statistics are <span className="text-foreground font-medium">disabled</span> for this site. Enable them from the site's Settings
                    page.
                  </>
                ) : (
                  <>
                    Install the <span className="text-foreground font-medium">GoAccess</span> service on this server to enable site statistics.
                  </>
                )}
              </CardContent>
            </Card>
          </>
        )}
      </Container>
    </ServerLayout>
  );
}

function StatsView({ server, site }: { server: Server; site: Site }) {
  const [month, setMonth] = useState<string | undefined>(undefined);
  const [refreshing, setRefreshing] = useState(false);
  const { data, isLoading, isError } = useSiteStats(server, site, month);
  const queryClient = useQueryClient();

  const selectedMonth = month ?? data?.month;

  useSocketListener(
    useCallback(
      (event) => {
        if (event.type !== 'site-stats.updated') return;
        const payload = event.data as { id?: number; months?: string[] } | undefined;
        if (!payload || payload.id !== site.id) return;
        if (!selectedMonth || payload.months?.includes(selectedMonth)) {
          queryClient.invalidateQueries({ queryKey: ['site-stats', site.id] });
        }
      },
      [site.id, selectedMonth, queryClient],
    ),
  );

  const refresh = () => {
    setRefreshing(true);
    router.post(
      route('site-stats.refresh', { server: server.id, site: site.id }),
      {},
      {
        preserveScroll: true,
        onError: (errors) => {
          const first = Object.values(errors)[0];
          toast.error(typeof first === 'string' && first.length > 0 ? first : 'Failed to refresh site statistics');
        },
        onFinish: () => setRefreshing(false),
      },
    );
  };

  const detail = data?.detail;

  return (
    <>
      <HeaderContainer>
        <Heading title="Stats" description="Web statistics for this site" />
        <div className="flex items-center gap-2">
          {data && data.months.length > 0 && (
            <Select value={selectedMonth} onValueChange={setMonth}>
              <SelectTrigger className="w-[160px]">
                <SelectValue placeholder="Select month" />
              </SelectTrigger>
              <SelectContent>
                {data.months.map((m) => (
                  <SelectItem key={m} value={m}>
                    {formatMonth(m)}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          )}
          <Button variant="outline" onClick={refresh} disabled={refreshing} aria-label="Refresh">
            <RefreshCwIcon className={refreshing ? 'animate-spin' : ''} />
            <span className="hidden lg:block">Refresh</span>
          </Button>
          <DocsButton />
        </div>
      </HeaderContainer>

      <SiteBanners site={site} />

      {data?.status && data.status.exit_code !== null && data.status.exit_code !== 0 && (
        <Card className="border-destructive/50">
          <CardContent className="text-destructive p-4 text-sm">
            The last statistics run failed{data.status.error ? `: ${data.status.error}` : ''}. Try Refresh, or Re-sync the GoAccess service.
          </CardContent>
        </Card>
      )}

      {isLoading && (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
          <Skeleton className="h-[200px]" />
          <Skeleton className="h-[200px]" />
          <Skeleton className="h-[200px]" />
        </div>
      )}

      {isError && (
        <Card>
          <CardContent className="text-muted-foreground p-6 text-sm">Failed to load site statistics. Try refreshing.</CardContent>
        </Card>
      )}

      {!isLoading && !isError && data && data.summary.length === 0 && !detail && (
        <Card>
          <CardContent className="text-muted-foreground p-6 text-sm">
            No statistics yet. Data is collected hourly — use Refresh to generate it now.
          </CardContent>
        </Card>
      )}

      {!isLoading && data && data.summary.length > 0 && (
        <div className="flex flex-col gap-2">
          <h3 className="text-foreground text-base font-medium">Growth (monthly)</h3>
          <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
            <StatsChart
              title="Visitors"
              value={formatNumber(data.summary.reduce((t, m) => t + m.visitors, 0))}
              color="var(--color-chart-1)"
              dataKey="visitors"
              labelKey="month"
              data={data.summary}
              formatLabel={formatMonth}
              valueFormatter={(v) => formatNumber(Number(v))}
            />
            <StatsChart
              title="Hits"
              value={formatNumber(data.summary.reduce((t, m) => t + m.hits, 0))}
              color="var(--color-chart-2)"
              dataKey="hits"
              labelKey="month"
              data={data.summary}
              formatLabel={formatMonth}
              valueFormatter={(v) => formatNumber(Number(v))}
            />
            <StatsChart
              title="Bandwidth"
              value={formatBytes(data.summary.reduce((t, m) => t + m.bandwidth, 0))}
              color="var(--color-chart-3)"
              dataKey="bandwidth"
              labelKey="month"
              data={data.summary}
              formatLabel={formatMonth}
              valueFormatter={(v) => formatBytes(Number(v))}
            />
          </div>
        </div>
      )}

      {!isLoading && detail && (
        <>
          <div className="flex flex-col gap-2">
            <div className="flex items-center justify-between">
              <h3 className="text-foreground text-base font-medium">{selectedMonth ? formatMonth(selectedMonth) : ''} detail</h3>
              {detail.generated_at && <span className="text-muted-foreground text-xs">Updated {new Date(detail.generated_at).toLocaleString()}</span>}
            </div>
            <div className="flex flex-col gap-4">
              <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                <StatCard title="Visitors" value={formatNumber(detail.totals.visitors)} />
                <StatCard title="Hits" value={formatNumber(detail.totals.hits)} />
                <StatCard title="Bandwidth" value={formatBytes(detail.totals.bandwidth)} />
              </div>
              <StatsChart
                title="Visitors per day"
                color="var(--color-chart-1)"
                dataKey="visitors"
                labelKey="date"
                data={detail.daily}
                formatLabel={formatDay}
                valueFormatter={(v) => formatNumber(Number(v))}
                height="small"
              />
              <StatsChart
                title="Hits per day"
                color="var(--color-chart-2)"
                dataKey="hits"
                labelKey="date"
                data={detail.daily}
                formatLabel={formatDay}
                valueFormatter={(v) => formatNumber(Number(v))}
                height="small"
              />
              <StatsChart
                title="Bandwidth per day"
                color="var(--color-chart-3)"
                dataKey="bandwidth"
                labelKey="date"
                data={detail.daily}
                formatLabel={formatDay}
                valueFormatter={(v) => formatBytes(Number(v))}
                height="small"
              />
            </div>
          </div>

          <div className="grid grid-cols-1 items-start gap-4 lg:grid-cols-2">
            <PanelCard title="Top pages" rows={detail.top_pages} />
            <PanelCard title="Referrers" rows={detail.referrers} />
          </div>

          <StatusCodesCard rows={detail.status_codes} />

          <PanelCard title="404 pages" rows={detail.not_found} />
        </>
      )}
    </>
  );
}

function DocsButton() {
  return (
    <a href="https://vitodeploy.com/docs/sites/stats" target="_blank">
      <Button variant="outline">
        <BookOpenIcon />
        <span className="hidden lg:block">Docs</span>
      </Button>
    </a>
  );
}

function StatCard({ title, value }: { title: string; value: string }) {
  return (
    <Card>
      <CardContent className="space-y-2 p-4">
        <h2 className="text-muted-foreground text-sm">{title}</h2>
        <span className="text-3xl font-bold">{value}</span>
      </CardContent>
    </Card>
  );
}

function PanelCard({ title, rows }: { title: string; rows: SiteStatsPanelRow[] }) {
  const columns: ColumnDef<SiteStatsPanelRow>[] = [
    {
      accessorKey: 'name',
      header: title,
      cell: ({ row }) => <div className="max-w-[280px] truncate">{row.original.name}</div>,
    },
    {
      accessorKey: 'hits',
      header: () => <div className="text-right">Hits</div>,
      cell: ({ row }) => <div className="text-right">{row.original.hits.toLocaleString()}</div>,
    },
    {
      accessorKey: 'visitors',
      header: () => <div className="text-right">Visitors</div>,
      cell: ({ row }) => <div className="text-right">{row.original.visitors.toLocaleString()}</div>,
    },
  ];

  return <DataTable columns={columns} data={rows} />;
}

function StatusCodesCard({ rows }: { rows: SiteStatsStatusCode[] }) {
  const columns: ColumnDef<SiteStatsStatusCode>[] = [
    {
      accessorKey: 'name',
      header: 'Status code',
    },
    {
      accessorKey: 'hits',
      header: () => <div className="text-right">Hits</div>,
      cell: ({ row }) => <div className="text-right">{row.original.hits.toLocaleString()}</div>,
    },
  ];

  return <DataTable columns={columns} data={rows} />;
}
