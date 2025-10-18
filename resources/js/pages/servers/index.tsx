import { Head, usePage } from '@inertiajs/react';

import { PaginatedData, type Configs } from '@/types';

import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/servers/components/columns';
import { Server } from '@/types/server';
import Heading from '@/components/heading';
import CreateServer from '@/pages/servers/components/create-server';
import Container from '@/components/container';
import { Button } from '@/components/ui/button';
import Layout from '@/layouts/app/layout';
import { BookOpenIcon, PlusIcon } from 'lucide-react';

type Page = {
  servers: PaginatedData<Server>;
  public_key: string;
  configs: Configs;
};

export default function Servers() {
  const page = usePage<Page>();
  return (
    <Layout>
      <Head title="Servers" />

      <Container className="max-w-5xl">
        <div className="flex items-start justify-between">
          <Heading title="Servers" description="All of the servers of your project listed here" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/create" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <CreateServer>
              <Button>
                <PlusIcon />
                Create server
              </Button>
            </CreateServer>
          </div>
        </div>
        <DataTable columns={columns} paginatedData={page.props.servers} searchable />
      </Container>
    </Layout>
  );
}
