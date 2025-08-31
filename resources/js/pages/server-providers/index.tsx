import SettingsLayout from '@/layouts/settings/layout';
import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import React from 'react';
import ConnectServerProvider from '@/pages/server-providers/components/connect-server-provider';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/server-providers/components/columns';
import { ServerProvider } from '@/types/server-provider';
import { PaginatedData } from '@/types';
import { BookOpenIcon } from 'lucide-react';

type Page = {
  serverProviders: PaginatedData<ServerProvider>;
  configs: {
    server_providers: string[];
  };
};

export default function ServerProviders() {
  const page = usePage<Page>();

  return (
    <SettingsLayout>
      <Head title="Server Providers" />
      <Container className="max-w-5xl">
        <div className="flex items-start justify-between">
          <Heading title="Server Providers" description="Here you can manage all of the server provider connections" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/settings/server-providers" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <ConnectServerProvider>
              <Button>Connect</Button>
            </ConnectServerProvider>
          </div>
        </div>

        <DataTable columns={columns} paginatedData={page.props.serverProviders} />
      </Container>
    </SettingsLayout>
  );
}
