import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import { PaginatedData } from '@/types';
import ServerLayout from '@/layouts/server/layout';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, PlusIcon } from 'lucide-react';
import Container from '@/components/container';
import { Service } from '@/types/service';
import InstallService from '@/pages/services/components/install';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/php/components/columns';
import { useRealtime } from '@/hooks/use-socket-events';

export default function PHP() {
  const page = usePage<{
    server: Server;
    installedVersions: PaginatedData<Service>;
  }>();

  const [installedVersions] = useRealtime<Service>(page.props.installedVersions, 'service');

  return (
    <ServerLayout>
      <Head title={`PHP - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="PHP" description="Here you can manage PHP" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/php" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <InstallService name="php">
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">Install</span>
              </Button>
            </InstallService>
          </div>
        </HeaderContainer>

        <DataTable columns={columns} paginatedData={installedVersions} />
      </Container>
    </ServerLayout>
  );
}
