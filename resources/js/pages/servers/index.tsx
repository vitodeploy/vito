import { Head, usePage } from '@inertiajs/react';

import { type Configs } from '@/types';

import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/servers/partials/columns';
import { Server } from '@/types/server';
import Heading from '@/components/heading';
import CreateServer from '@/pages/servers/partials/create-server';
import Container from '@/components/container';
import { PlusIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import React from 'react';
import Layout from '@/layouts/app/layout';

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
    <Layout>
      <Head title="Servers" />

      <Container className="max-w-5xl">
        <div className="flex items-start justify-between">
          <Heading title="Servers" />
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
    </Layout>
  );
}
