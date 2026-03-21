import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import ServerLayout from '@/layouts/server/layout';
import SiteBanners from '@/components/site-banners';
import { DataTable } from '@/components/data-table';
import { PaginatedData } from '@/types';
import { columns } from '@/pages/commands/components/execution-columns';
import { Site } from '@/types/site';
import { CommandExecution } from '@/types/command-execution';
import { useRealtime } from '@/hooks/use-socket-events';

type Page = {
  server: Server;
  site: Site;
  executions: PaginatedData<CommandExecution>;
};

export default function Show() {
  const page = usePage<Page>();
  const [executions] = useRealtime<CommandExecution>(page.props.executions, 'command-execution');

  return (
    <ServerLayout>
      <Head title={`Executions - ${page.props.site.domain} - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title={`Command executions`} description="Here you can see the command executions" />
        </HeaderContainer>

        <SiteBanners site={page.props.site} />

        <DataTable columns={columns} paginatedData={executions} />
      </Container>
    </ServerLayout>
  );
}
