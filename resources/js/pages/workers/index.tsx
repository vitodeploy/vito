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
import { Worker } from '@/types/worker';
import WorkerForm from '@/pages/workers/components/form';
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
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import LogOutput from '@/components/log-output';

function Delete({ worker }: { worker: Worker }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.delete(route('workers.destroy', { server: worker.server_id, worker: worker }), {
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
          <DialogTitle>Delete worker</DialogTitle>
          <DialogDescription className="sr-only">Delete worker</DialogDescription>
        </DialogHeader>
        <p className="p-4">Are you sure you want to delete this worker? This action cannot be undone.</p>
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

function Action({ type, worker }: { type: 'start' | 'stop' | 'restart'; worker: Worker }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.post(route(`workers.${type}`, { server: worker.server_id, worker: worker }), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };
  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem onSelect={(e) => e.preventDefault()} className="capitalize">
          {type}
        </DropdownMenuItem>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>
            <span className="capitalize">{type}</span> worker
          </DialogTitle>
          <DialogDescription className="sr-only">{type} worker</DialogDescription>
        </DialogHeader>
        <p className="p-4">Are you sure you want to {type} the worker?</p>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button variant={['stop'].includes(type) ? 'destructive' : 'default'} disabled={form.processing} onClick={submit} className="capitalize">
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            {type}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function Logs({ worker }: { worker: Worker }) {
  const [open, setOpen] = useState(false);

  const query = useQuery({
    queryKey: ['workerLog', worker.id],
    queryFn: async () => {
      const response = await axios.get(route('workers.logs', { server: worker.server_id, worker: worker.id }));
      return response.data.logs;
    },
    refetchInterval: 2500,
    enabled: open,
  });

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Logs</DropdownMenuItem>
      </DialogTrigger>
      <DialogContent className="sm:max-w-5xl">
        <DialogHeader>
          <DialogTitle>Worker logs</DialogTitle>
          <DialogDescription className="sr-only">View worker logs</DialogDescription>
        </DialogHeader>
        <LogOutput>{query.isLoading ? 'Loading...' : query.data}</LogOutput>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Close</Button>
          </DialogClose>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

export default function WorkerIndex() {
  const page = usePage<{
    server: Server;
    workers: DynamicTableData;
    site?: Site;
  }>();

  return (
    <ServerLayout>
      <Head title={`Workers - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading
            title="Workers"
            description={page.props.site ? `Here you can manage ${page.props.site.domain}'s workers` : "Here you can manage server's workers"}
          />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/workers" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <WorkerForm serverId={page.props.server.id} site={page.props.site}>
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">Create</span>
              </Button>
            </WorkerForm>
          </div>
        </HeaderContainer>

        {page.props.site && <SiteBanners site={page.props.site} />}

        <DynamicTable
          tableData={page.props.workers}
          actions={(worker) => (
            <div className="flex items-center justify-end">
              <DropdownMenu modal={false}>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" className="h-8 w-8 p-0">
                    <span className="sr-only">Open menu</span>
                    <MoreVerticalIcon />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <WorkerForm serverId={(worker as unknown as Worker).server_id} worker={worker as unknown as Worker}>
                    <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Edit</DropdownMenuItem>
                  </WorkerForm>
                  <Action type="start" worker={worker as unknown as Worker} />
                  <Action type="stop" worker={worker as unknown as Worker} />
                  <Action type="restart" worker={worker as unknown as Worker} />
                  <Logs worker={worker as unknown as Worker} />
                  <DropdownMenuSeparator />
                  <Delete worker={worker as unknown as Worker} />
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          )}
        />
      </Container>
    </ServerLayout>
  );
}
