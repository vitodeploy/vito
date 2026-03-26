import { Head, Link, usePage } from '@inertiajs/react';

import { type Configs } from '@/types';

import { VitoTable } from '@/components/vito-table';
import Heading from '@/components/heading';
import CreateServer from '@/pages/servers/components/create-server';
import Container from '@/components/container';
import { Button } from '@/components/ui/button';
import Layout from '@/layouts/app/layout';
import { BookOpenIcon, EyeIcon, PlusIcon } from 'lucide-react';
import type { InertiaTableData, Row } from 'inertia-table-react';

type Page = {
  servers: InertiaTableData;
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
        <VitoTable
          tableData={page.props.servers}
          actions={(row: Row) => (
            <div className="flex items-center justify-end">
              <Link href={route('servers.show', { server: row.id })} prefetch>
                <Button variant="outline" size="sm">
                  <EyeIcon />
                </Button>
              </Link>
            </div>
          )}
        />
      </Container>
    </Layout>
  );
}
