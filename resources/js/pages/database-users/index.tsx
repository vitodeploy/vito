import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import ServerLayout from '@/layouts/server/layout';
import { DataTable } from '@/components/data-table';
import { BookOpenIcon, PlusIcon } from 'lucide-react';
import CreateDatabaseUser from '@/pages/database-users/components/create-database-user';
import SyncUsers from '@/pages/database-users/components/sync-users';
import { DatabaseUser } from '@/types/database-user';
import { columns } from '@/pages/database-users/components/columns';
import { PaginatedData } from '@/types';

type Page = {
  server: Server;
  databaseUsers: PaginatedData<DatabaseUser>;
};

export default function Databases() {
  const page = usePage<Page>();

  return (
    <ServerLayout>
      <Head title={`Users - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Users" description="Here you can manage the database users and their permissions" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/database" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <SyncUsers server={page.props.server} />
            <CreateDatabaseUser server={page.props.server.id}>
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">Create</span>
              </Button>
            </CreateDatabaseUser>
          </div>
        </HeaderContainer>

        <DataTable columns={columns} paginatedData={page.props.databaseUsers} />
      </Container>
    </ServerLayout>
  );
}
