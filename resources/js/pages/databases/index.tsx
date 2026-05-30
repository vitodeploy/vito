import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import type { Database } from '@/types/database';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import CreateDatabase from '@/pages/databases/components/create-database';
import { Button } from '@/components/ui/button';
import ServerLayout from '@/layouts/server/layout';
import { VitoTable } from '@/components/vito-table';
import Delete from '@/pages/databases/components/delete';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { BookOpenIcon, MoreVerticalIcon, PlusIcon } from 'lucide-react';
import SyncDatabases from '@/pages/databases/components/sync-databases';
import type { InertiaTableData, Row } from '@forjedio/inertia-table-react';
import { asRow } from '@/lib/inertia-table';

type Page = {
  server: Server;
  databases: InertiaTableData;
};

export default function Databases() {
  const page = usePage<Page>();

  const dbType = page.props.server.services['database'];
  const defaultCharset = dbType === 'postgresql' ? 'UTF8' : 'utf8mb4';
  const defaultCollation = dbType === 'postgresql' ? 'C.utf8' : 'utf8mb4_0900_ai_ci';

  return (
    <ServerLayout>
      <Head title={`Databases - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Databases" description="Here you can manage the databases" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/database" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <SyncDatabases server={page.props.server} />
            <CreateDatabase server={page.props.server.id} defaultCharset={defaultCharset} defaultCollation={defaultCollation}>
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">Create</span>
              </Button>
            </CreateDatabase>
          </div>
        </HeaderContainer>

        <VitoTable
          tableData={page.props.databases}
          actions={(row: Row) => (
            <div className="flex items-center justify-end">
              <DropdownMenu modal={false}>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" className="h-8 w-8 p-0">
                    <span className="sr-only">Open menu</span>
                    <MoreVerticalIcon />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <Delete database={asRow<Database>(row, ['id', 'name', 'server_id'])} />
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          )}
        />
      </Container>
    </ServerLayout>
  );
}
