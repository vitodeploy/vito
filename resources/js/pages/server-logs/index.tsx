import { Head, usePage } from '@inertiajs/react';
import { PaginatedData } from '@/types';
import { ServerLog } from '@/types/server-log';
import { Server } from '@/types/server';
import ServerLayout from '@/layouts/server/layout';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/server-logs/components/columns';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, PlusIcon } from 'lucide-react';
import LogForm from '@/pages/server-logs/components/form';

export default function ServerLogs() {
  const page = usePage<{
    title: string;
    server: Server;
    logs: PaginatedData<ServerLog>;
    remote: boolean;
  }>();

  return (
    <ServerLayout>
      <Head title={`${page.props.title} - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title={page.props.title} description="Here you can see all logs" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/logs" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            {page.props.remote && (
              <LogForm>
                <Button>
                  <PlusIcon />
                  <span className="hidden lg:block">Create</span>
                </Button>
              </LogForm>
            )}
          </div>
        </HeaderContainer>

        <DataTable columns={columns} paginatedData={page.props.logs} searchable sortable />
      </Container>
    </ServerLayout>
  );
}
