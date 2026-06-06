import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { BreadcrumbHeader } from '@/components/breadcrumb-header';
import ServerLayout from '@/layouts/server/layout';
import SiteBanners from '@/components/site-banners';
import { DataTable } from '@/components/data-table';
import { BreadcrumbItem, PaginatedData } from '@/types';
import { columns } from '@/pages/commands/components/execution-columns';
import { Site } from '@/types/site';
import { Command } from '@/types/command';
import { CommandExecution } from '@/types/command-execution';
import { useRealtime } from '@/hooks/use-socket-events';

type Page = {
  server: Server;
  site: Site;
  command: Command;
  executions: PaginatedData<CommandExecution>;
};

export default function Show() {
  const page = usePage<Page>();
  const [executions] = useRealtime<CommandExecution>(page.props.executions, 'command-execution', { command_id: page.props.command.id });

  const breadcrumbs: BreadcrumbItem[] = [
    {
      title: 'Commands',
      href: route('commands', { server: page.props.server.id, site: page.props.site.id }),
    },
    {
      title: page.props.command.name,
      href: route('commands.show', { server: page.props.server.id, site: page.props.site.id, command: page.props.command.id }),
    },
  ];

  return (
    <ServerLayout>
      <Head title={`History of ${page.props.command.name} - ${page.props.site.domain} - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <BreadcrumbHeader breadcrumbs={breadcrumbs}>
            <Heading title={`History of ${page.props.command.name}`} description="Here you can see the command executions" />
          </BreadcrumbHeader>
        </HeaderContainer>

        <SiteBanners site={page.props.site} />

        <DataTable columns={columns} paginatedData={executions} />
      </Container>
    </ServerLayout>
  );
}
