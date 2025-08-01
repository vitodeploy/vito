import { Head, usePage } from '@inertiajs/react';
import { Site } from '@/types/site';
import ServerLayout from '@/layouts/server/layout';
import { Server } from '@/types/server';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
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
          <Heading title="Logs" description="Here you can see your site's logs" />
        </HeaderContainer>

        <DataTable columns={columns} paginatedData={page.props.logs} searchable />
      </Container>
    </ServerLayout>
  );
}
