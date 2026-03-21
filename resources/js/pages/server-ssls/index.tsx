import ServerLayout from '@/layouts/server/layout';
import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import { PaginatedData } from '@/types';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/server-ssls/components/columns';
import { SSL } from '@/types/ssl';
import { Domain } from '@/types/domain';
import { useRealtime } from '@/hooks/use-socket-events';
import { Button } from '@/components/ui/button';
import { BookOpenIcon } from 'lucide-react';
import CreateServerSsl from '@/pages/server-ssls/components/create-server-ssl';

export default function ServerSsls() {
  const page = usePage<{
    server: Server;
    ssls: PaginatedData<SSL>;
    domains: Domain[];
  }>();

  const [ssls] = useRealtime<SSL>(page.props.ssls, 'ssl');

  return (
    <ServerLayout>
      <Head title={`SSL - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="SSL" description="Global Server level SSL management for Custom or Wildcard certificates" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/ssl" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <CreateServerSsl server={page.props.server} domains={page.props.domains}>
              <Button>Add</Button>
            </CreateServerSsl>
          </div>
        </HeaderContainer>

        <DataTable columns={columns} paginatedData={ssls} />
      </Container>
    </ServerLayout>
  );
}
