import type { Server } from '@/types/server';
import type { ServerLog } from '@/types/server-log';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/server-logs/components/columns';
import { usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';

export default function ServerOverview() {
  const page = usePage<{
    server: Server;
    logs: {
      data: ServerLog[];
    };
  }>();

  return (
    <Container className="max-w-3xl">
      <Heading title="Overview" description="Here you can see an overview of your server" />
      <DataTable columns={columns} data={page.props.logs.data} />
    </Container>
  );
}
