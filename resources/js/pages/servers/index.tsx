import { Head, usePage, usePoll } from '@inertiajs/react';

import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/app-layout';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/servers/columns';
import { Server } from '@/types/server';
import Heading from '@/components/heading';
import CreateServer from '@/pages/servers/create-server';

type Response = {
  servers: {
    data: Server[];
  };
  providers: string[];
  public_key: string;
};

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Home',
    href: '/',
  },
];

export default function Servers() {
  const page = usePage<Response>();
  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="Servers" />

      <div className="container mx-auto py-10">
        <div className="flex items-start justify-between">
          <Heading title="Servers" description="All of the servers on your project are here" />
          <div className="flex items-center gap-2">
            <CreateServer providers={page.props.providers} public_key={page.props.public_key} />
          </div>
        </div>
        <DataTable columns={columns} data={page.props.servers.data} />
      </div>
    </AppLayout>
  );
}
