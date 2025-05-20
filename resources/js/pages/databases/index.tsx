import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import type { Database } from '@/types/database';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import CreateDatabase from '@/pages/databases/components/create-database';
import { Button } from '@/components/ui/button';
import ServerLayout from '@/layouts/server/layout';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/databases/components/columns';
import React from 'react';
import { BookOpenIcon, PlusIcon } from 'lucide-react';
import SyncDatabases from '@/pages/databases/components/sync-databases';

type Page = {
  server: Server;
  databases: {
    data: Database[];
  };
};

export default function Databases() {
  const page = usePage<Page>();

  return (
    <ServerLayout server={page.props.server}>
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
            <CreateDatabase server={page.props.server}>
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">Create</span>
              </Button>
            </CreateDatabase>
          </div>
        </HeaderContainer>

        <DataTable columns={columns} data={page.props.databases.data} />
      </Container>
    </ServerLayout>
  );
}
