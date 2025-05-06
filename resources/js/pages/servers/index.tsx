import { Head, usePage } from '@inertiajs/react';

import { type Configs } from '@/types';

import AppLayout from '@/layouts/app-layout';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/servers/columns';
import { Server } from '@/types/server';
import Heading from '@/components/heading';
import CreateServer from '@/pages/servers/create-server';
import Container from '@/components/container';
import { PlusIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import React from 'react';

type Response = {
  servers: {
    data: Server[];
  };
  public_key: string;
  configs: Configs;
};

export default function Servers() {
  const page = usePage<Response>();
  return (
    <AppLayout>
      <Head title="Servers" />

      <Container>
        <div className="flex items-start justify-between">
          <Heading title="Servers" description="All of the servers on your project are here" />
          <div className="flex items-center gap-2">
            <CreateServer>
              <Button variant="outline">
                <PlusIcon /> Create new server
              </Button>
            </CreateServer>
          </div>
        </div>
        <DataTable columns={columns} data={page.props.servers.data} />
      </Container>
    </AppLayout>
  );
}
