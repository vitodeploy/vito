import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import ServerLayout from '@/layouts/server/layout';
import SiteBanners from '@/components/site-banners';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, LoaderCircleIcon, MoreVerticalIcon, PlusIcon } from 'lucide-react';
import Container from '@/components/container';
import { DynamicTable } from '@/components/dynamic-table';
import { DynamicTableData } from '@/types/dynamic-table';
import { CronJob } from '@/types/cronjob';
import CronJobForm from '@/pages/cronjobs/components/form';
import SyncCronJobs from '@/pages/cronjobs/components/sync-cronjobs';
import { Site } from '@/types/site';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { useForm } from '@inertiajs/react';
import FormSuccessful from '@/components/form-successful';
import { useState } from 'react';

function Action({ type, cronJob, site }: { type: 'enable' | 'disable'; cronJob: CronJob; site?: Site }) {
  const form = useForm();

  const submit = () => {
    const routeName = site
      ? type === 'enable'
        ? 'cronjobs.site.enable'
        : 'cronjobs.site.disable'
      : type === 'enable'
        ? 'cronjobs.enable'
        : 'cronjobs.disable';

    const routeParams = site ? { server: cronJob.server_id, site: site.id, cronJob: cronJob.id } : { server: cronJob.server_id, cronJob: cronJob.id };

    form.post(route(routeName, routeParams), {
      onSuccess: () => {
        // The page will refresh automatically
      },
    });
  };

  return (
    <DropdownMenuItem onSelect={(e) => e.preventDefault()} onClick={submit} disabled={form.processing}>
      {form.processing && <LoaderCircleIcon className="mr-2 h-4 w-4 animate-spin" />}
      <FormSuccessful successful={form.recentlySuccessful} />
      {type === 'enable' ? 'Enable' : 'Disable'}
    </DropdownMenuItem>
  );
}

function Delete({ cronJob, site }: { cronJob: CronJob; site?: Site }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    const routeName = site ? 'cronjobs.site.destroy' : 'cronjobs.destroy';
    const routeParams = site ? { server: cronJob.server_id, site: site.id, cronJob: cronJob } : { server: cronJob.server_id, cronJob: cronJob };

    form.delete(route(routeName, routeParams), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };
  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem variant="destructive" onSelect={(e) => e.preventDefault()}>
          Delete
        </DropdownMenuItem>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete cronJob</DialogTitle>
          <DialogDescription className="sr-only">Delete cronJob</DialogDescription>
        </DialogHeader>
        <p className="p-4">Are you sure you want to delete this cron job? This action cannot be undone.</p>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button variant="destructive" disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Delete
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

export default function CronJobIndex() {
  const page = usePage<{
    server: Server;
    cronjobs: DynamicTableData;
    site?: Site;
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

        {page.props.site && <SiteBanners site={page.props.site} />}

        <DynamicTable
          tableData={page.props.cronjobs}
          actions={(cronJob) => (
            <div className="flex items-center justify-end">
              <DropdownMenu modal={false}>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" className="h-8 w-8 p-0">
                    <span className="sr-only">Open menu</span>
                    <MoreVerticalIcon />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <CronJobForm serverId={(cronJob as unknown as CronJob).server_id} site={page.props.site} cronJob={cronJob as unknown as CronJob}>
                    <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Edit</DropdownMenuItem>
                  </CronJobForm>
                  {(cronJob as unknown as CronJob).status === 'disabled' && (
                    <Action type="enable" cronJob={cronJob as unknown as CronJob} site={page.props.site} />
                  )}
                  {(cronJob as unknown as CronJob).status === 'ready' && (
                    <Action type="disable" cronJob={cronJob as unknown as CronJob} site={page.props.site} />
                  )}
                  <DropdownMenuSeparator />
                  <Delete cronJob={cronJob as unknown as CronJob} site={page.props.site} />
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          )}
        />
      </Container>
    </ServerLayout>
  );
}
