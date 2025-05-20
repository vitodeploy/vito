import type { Server } from '@/types/server';
import type { ServerLog } from '@/types/server-log';
import Container from '@/components/container';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/server-logs/components/columns';
import { usePage } from '@inertiajs/react';
import Heading from '@/components/heading';

export default function InstallingServer() {
  const page = usePage<{
    server: Server;
    logs: {
      data: ServerLog[];
    };
  }>();

  return (
    <Container className="max-w-5xl">
      <Heading title="Installing" description="Here you can see the installation logs" />
      <DataTable columns={columns} data={page.props.logs.data} />{' '}
    </Container>
  );
}
