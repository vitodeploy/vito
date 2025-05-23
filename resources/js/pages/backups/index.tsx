import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import ServerLayout from '@/layouts/server/layout';
import React from 'react';
import { BookOpenIcon, PlusIcon } from 'lucide-react';
import { Backup } from '@/types/backup';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/backups/components/columns';
import CreateBackup from '@/pages/backups/components/create-backup';

type Page = {
  server: Server;
  backups: {
    data: Backup[];
  };
};

export default function Backups() {
  const page = usePage<Page>();

  return (
    <ServerLayout server={page.props.server}>
      <Head title={`Backups - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Backups" description="Here you can manage the backups of your database" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/database" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <CreateBackup server={page.props.server}>
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">Create</span>
              </Button>
            </CreateBackup>
          </div>
        </HeaderContainer>

        <DataTable columns={columns} data={page.props.backups.data} />
      </Container>
    </ServerLayout>
  );
}
