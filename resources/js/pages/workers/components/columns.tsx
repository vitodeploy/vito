import { ColumnDef } from '@tanstack/react-table';
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
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon, MoreVerticalIcon } from 'lucide-react';
import FormSuccessful from '@/components/form-successful';
import React, { useState } from 'react';
import { Worker } from '@/types/worker';
import { Badge } from '@/components/ui/badge';
import DateTime from '@/components/date-time';
import WorkerForm from '@/pages/workers/components/form';
import CopyableBadge from '@/components/copyable-badge';
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

export const columns: ColumnDef<Worker>[] = [
  {
    accessorKey: 'name',
    header: 'Name',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    accessorKey: 'command',
    header: 'Command',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return <CopyableBadge text={row.original.command} />;
    },
  },
  {
    accessorKey: 'user',
    header: 'User',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    accessorKey: 'numprocs',
    header: 'Numprocs',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    accessorKey: 'created_at',
    header: 'Created at',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return <DateTime date={row.original.created_at} />;
    },
  },
  {
    accessorKey: 'status',
    header: 'Status',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return <Badge variant={row.original.status_color}>{row.original.status}</Badge>;
    },
  },
  {
    id: 'actions',
    enableColumnFilter: false,
    enableSorting: false,
    cell: ({ row }) => {
      return (
        <div className="flex items-center justify-end">
          <DropdownMenu modal={false}>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                <span className="sr-only">Open menu</span>
                <MoreVerticalIcon />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <WorkerForm serverId={row.original.server_id} worker={row.original}>
                <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Edit</DropdownMenuItem>
              </WorkerForm>
              <Action type="start" worker={row.original} />
              <Action type="stop" worker={row.original} />
              <Action type="restart" worker={row.original} />
              <Logs worker={row.original} />
              <DropdownMenuSeparator />
              <Delete worker={row.original} />
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];
