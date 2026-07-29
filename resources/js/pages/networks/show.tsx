import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { BookOpenIcon } from 'lucide-react';
import { Card, CardContent } from '@/components/ui/card';
import { ReactNode } from 'react';
import NetworkLayout from '@/layouts/network/layout';
import { useRealtime, useRealtimeRecord } from '@/hooks/use-socket-events';
import { DataTable } from '@/components/data-table';
import { networkLogColumns } from '@/pages/networks/components/log-columns';
import { PaginatedData } from '@/types';
import { ServerLog } from '@/types/server-log';
import { Network, NetworkStats } from '@/types/network';

function StatTile({ label, children }: { label: string; children: ReactNode }) {
  return (
    <Card>
      <CardContent className="flex flex-col gap-1 p-4">
        <span className="text-muted-foreground text-xs">{label}</span>
        <span className="text-2xl font-semibold">{children}</span>
      </CardContent>
    </Card>
  );
}

function MetaChip({ label, children }: { label: string; children: ReactNode }) {
  return (
    <span className="bg-muted/50 inline-flex items-center gap-1.5 rounded-md border px-2.5 py-1 text-xs">
      <span className="text-muted-foreground">{label}</span>
      <span className="font-medium">{children}</span>
    </span>
  );
}

export default function NetworkOverview() {
  const page = usePage<{ network: Network; stats: NetworkStats; logs: PaginatedData<ServerLog> }>();
  const network = useRealtimeRecord<Network>(page.props.network, 'network')!;
  const { stats } = page.props;
  const [logs] = useRealtime<ServerLog>(page.props.logs, 'server-log', { network_id: network.id });
  const isWireGuard = network.type_value === 'wireguard';

  return (
    <NetworkLayout>
      <Head title={`Overview - ${network.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <div className="space-y-3">
            <Heading title="Overview" description={network.name} />
            <div className="flex flex-wrap items-center gap-2">
              <Badge variant={network.type_color}>{network.type}</Badge>
              <Badge variant={network.status_color}>{network.status}</Badge>
              {network.cidr && (
                <MetaChip label="CIDR">
                  <span className="font-mono">{network.cidr}</span>
                </MetaChip>
              )}
              {isWireGuard && <MetaChip label="Pool">{network.addressing_pool}</MetaChip>}
              {isWireGuard && network.port && (
                <MetaChip label="Port">
                  <span className="font-mono">{network.port}</span>
                </MetaChip>
              )}
            </div>
          </div>
          <a href="https://vitodeploy.com/docs/networks/overview" target="_blank" rel="noreferrer">
            <Button variant="outline">
              <BookOpenIcon />
              <span className="hidden lg:block">Docs</span>
            </Button>
          </a>
        </HeaderContainer>

        <div className="grid grid-cols-2 gap-4 lg:grid-cols-3">
          <StatTile label="Servers">{stats.servers}</StatTile>
          <StatTile label="Peers">{isWireGuard ? stats.peers : 'N/A'}</StatTile>
          <StatTile label="Firewall rules">{stats.firewall_rules}</StatTile>
        </div>

        <div className="space-y-4">
          <Heading title="Recent activity" description="Logs from this network's servers" />
          <DataTable columns={networkLogColumns} paginatedData={logs} searchable sortable />
        </div>
      </Container>
    </NetworkLayout>
  );
}
