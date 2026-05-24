import type { Server } from '@/types/server';
import type { ServerLog } from '@/types/server-log';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/server-logs/components/columns';
import { usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { PaginatedData } from '@/types';
import MetricsCards from '@/pages/monitoring/components/metrics-cards';
import ServerBanners from '@/components/server-banners';
import { useRealtimeRecord } from '@/hooks/use-socket-events';

export default function ServerOverview() {
  const page = usePage<{
    server: Server;
    logs: PaginatedData<ServerLog>;
  }>();

  const server = useRealtimeRecord<Server>(page.props.server, 'server')!;

  return (
    <Container className="max-w-5xl">
      <Heading title="Overview" description="Here you can see an overview of your server" />
      <ServerBanners server={server} />
      <MetricsCards server={server} />
      <DataTable columns={columns} paginatedData={page.props.logs} />
    </Container>
  );
}
