import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import AddDomain from '@/pages/domains/components/add-domain';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/domains/components/columns';
import { Domain } from '@/types/domain';
import { PaginatedData } from '@/types';
import { BookOpenIcon, PlusIcon } from 'lucide-react';
import Layout from '@/layouts/app/layout';

type Page = {
  domains: PaginatedData<Domain>;
};

export default function Domains() {
  const page = usePage<Page>();

  return (
    <Layout>
      <Head title="Domains" />
      <Container className="max-w-5xl">
        <div className="flex items-start justify-between">
          <Heading title="Domains" description="All of the domains of your project listed here" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/domains" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <AddDomain>
              <Button>
                <PlusIcon />
                Add Domain
              </Button>
            </AddDomain>
          </div>
        </div>
        <DataTable columns={columns} paginatedData={page.props.domains} searchable />
      </Container>
    </Layout>
  );
}
