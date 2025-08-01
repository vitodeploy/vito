import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import { Site } from '@/types/site';
import ServerLayout from '@/layouts/server/layout';
import Layout from '@/layouts/app/layout';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, PlusIcon } from 'lucide-react';
import { DataTable } from '@/components/data-table';
import getColumns from '@/pages/sites/components/columns';
import { PaginatedData } from '@/types';
import CreateSite from '@/pages/sites/components/create-site';

type Page = {
  server?: Server;
  sites: PaginatedData<Site>;
};

export default function Sites() {
  const page = usePage<Page>();

  const Comp = page.props.server ? ServerLayout : Layout;

  return (
    <Comp>
      <Head title={`Sites ${page.props.server ? ' - ' + page.props.server.name : ''}`} />
      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Sites" description="Here you can manage websites" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/sites/site-types" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <CreateSite server={page.props.server}>
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">Create site</span>
              </Button>
            </CreateSite>
          </div>
        </HeaderContainer>

        <DataTable columns={getColumns(page.props.server)} paginatedData={page.props.sites} searchable />
      </Container>
    </Comp>
  );
}
