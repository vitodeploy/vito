import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import ServerLayout from '@/layouts/server/layout';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { PlusIcon } from 'lucide-react';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/applications/components/columns';
import { PaginatedData } from '@/types';
import { Application } from '@/types/application';
import CreateApplication from '@/pages/applications/components/create-application';
import { useRealtime } from '@/hooks/use-socket-events';

type Page = {
  server: Server;
  applications: PaginatedData<Application>;
};

export default function Applications() {
  const page = usePage<Page>();

  const [applications] = useRealtime<Application>(page.props.applications, 'application');

  return (
    <ServerLayout>
      <Head title={`Applications - ${page.props.server.name}`} />
      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Applications" description="Manage applications on your server" />
          <div className="flex items-center gap-2">
            <CreateApplication server={page.props.server}>
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">Create application</span>
              </Button>
            </CreateApplication>
          </div>
        </HeaderContainer>

        <DataTable columns={columns} paginatedData={applications} searchable />
      </Container>
    </ServerLayout>
  );
}
