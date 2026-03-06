import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import { PaginatedData } from '@/types';
import ServerLayout from '@/layouts/server/layout';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, PlusIcon } from 'lucide-react';
import Container from '@/components/container';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/services/components/columns';
import { Service } from '@/types/service';
import InstallService from '@/pages/services/components/install';
import { useRealtime } from '@/hooks/use-socket-events';

export default function WorkerIndex() {
  const page = usePage<{
    server: Server;
    services: PaginatedData<Service>;
  }>();

  const [services] = useRealtime<Service>(page.props.services, 'service');

  return (
    <ServerLayout>
      <Head title={`Services - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Services" description="Here you can manage server's services" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/services" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <InstallService>
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">Install</span>
              </Button>
            </InstallService>
          </div>
        </HeaderContainer>

        <DataTable columns={columns} paginatedData={services} />
      </Container>
    </ServerLayout>
  );
}
