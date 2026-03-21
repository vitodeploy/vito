import { Head, usePage } from '@inertiajs/react';
import { Site } from '@/types/site';
import ServerLayout from '@/layouts/server/layout';
import SiteBanners from '@/components/site-banners';
import { Server } from '@/types/server';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon } from 'lucide-react';
import React from 'react';
import { PaginatedData } from '@/types';
import { ServerLog } from '@/types/server-log';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/server-logs/components/columns';

type Page = {
  server: Server;
  site: Site;
  logs: PaginatedData<ServerLog>;
};

export default function ShowSite() {
  const page = usePage<Page>();

  return (
    <ServerLayout>
      <Head title={`${page.props.site.domain} - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Application" description="Here you can manage the deployed application" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/sites/application" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
          </div>
        </HeaderContainer>

        <SiteBanners site={page.props.site} />

        <DataTable columns={columns} paginatedData={page.props.logs} />
      </Container>
    </ServerLayout>
  );
}
