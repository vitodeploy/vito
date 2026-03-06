import { Head, usePage } from '@inertiajs/react';
import { Site } from '@/types/site';
import AppWithDeployment from '@/pages/application/components/app-with-deployment';
import LoadBalancer from '@/pages/application/components/load-balancer';
import siteHelper from '@/lib/site-helper';
import ServerLayout from '@/layouts/server/layout';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { DataTable } from '@/components/data-table';
import { columns } from '../server-logs/components/columns';
import { Server } from '@/types/server';
import { PaginatedData } from '@/types';
import { ServerLog } from '@/types/server-log';
import { useRealtime } from '@/hooks/use-socket-events';

export default function Application() {
  const page = usePage<{
    server: Server;
    site: Site;
    logs: PaginatedData<ServerLog>;
  }>();

  const [logs] = useRealtime<ServerLog>(page.props.logs, 'server-log');

  siteHelper.storeSite(page.props.site);

  if (page.props.site.status !== 'ready') {
    return (
      <ServerLayout>
        <Head title={`${page.props.site.domain} - ${page.props.server.name}`} />

        <Container className="max-w-5xl">
          <HeaderContainer>
            <Heading title="Installing site" description="Your site is being installed. Here you can see the logs" />
          </HeaderContainer>

          <DataTable columns={columns} paginatedData={logs} />
        </Container>
      </ServerLayout>
    );
  }

  if (page.props.site.type === 'load-balancer') {
    return <LoadBalancer />;
  }

  return <AppWithDeployment />;
}
