import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import { PaginatedData } from '@/types';
import ServerLayout from '@/layouts/server/layout';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, PlusIcon } from 'lucide-react';
import Container from '@/components/container';
import { DataTable } from '@/components/data-table';
import { CronJob } from '@/types/cronjob';
import { columns } from '@/pages/cronjobs/components/columns';
import CronJobForm from '@/pages/cronjobs/components/form';
import SyncCronJobs from '@/pages/cronjobs/components/sync-cronjobs';
import { Site } from '@/types/site';

export default function CronJobIndex() {
  const page = usePage<{
    server: Server;
    cronjobs: PaginatedData<CronJob>;
    site?: Site;
    sites?: Array<{ id: number; domain: string }>;
  }>();

  return (
    <ServerLayout>
      <Head title={`Cron jobs - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading
            title="Cron jobs"
            description={page.props.site ? `Here you can manage ${page.props.site.domain}'s cron jobs` : "Here you can manage server's cron jobs"}
          />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/cronjobs" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <SyncCronJobs server={page.props.server} />
            <CronJobForm serverId={page.props.server.id} site={page.props.site}>
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">Create</span>
              </Button>
            </CronJobForm>
          </div>
        </HeaderContainer>

        <DataTable columns={columns(page.props.site, page.props.sites)} paginatedData={page.props.cronjobs} />
      </Container>
    </ServerLayout>
  );
}
