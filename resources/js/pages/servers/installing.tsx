import type { Server } from '@/types/server';
import type { ServerLog } from '@/types/server-log';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Progress } from '@/components/ui/progress';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/server-logs/columns';
import { usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

export default function InstallingServer() {
  const page = usePage<{
    server: Server;
    logs: {
      data: ServerLog[];
    };
  }>();

  return (
    <Container>
      <div className="flex items-start justify-between">
        <Heading title={`Installing ${page.props.server.name}`} description="Your server is being installed" />
        {page.props.server.status === 'installation_failed' && <Button variant="destructive">Delete</Button>}
      </div>
      <Progress value={parseInt(page.props.server.progress || '0')} />
      <div className="mt-2 text-center">{page.props.server.progress}%</div>
      <DataTable columns={columns} data={page.props.logs.data} />
    </Container>
  );
}
