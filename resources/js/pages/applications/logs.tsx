import ServerLayout from '@/layouts/server/layout';
import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import { Application } from '@/types/application';
import { PaginatedData } from '@/types';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/server-logs/components/columns';
import { ServerLog } from '@/types/server-log';
import { useRealtime } from '@/hooks/use-socket-events';

export default function ApplicationLogs() {
  const page = usePage<{
    server: Server;
    application: Application;
    logs: PaginatedData<ServerLog>;
  }>();

  const [logs] = useRealtime<ServerLog>(page.props.logs, 'server-log');

  return (
    <ServerLayout>
      <Head title={`Logs - ${page.props.application.domain}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Logs" description="Application logs" />
        </HeaderContainer>

        <DataTable columns={columns} paginatedData={logs} searchable />
      </Container>
    </ServerLayout>
  );
}
