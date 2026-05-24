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
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { WorkerAction, WorkerLogs } from '@/pages/workers/components/worker-row-actions';

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

function BootstrapLockedItem({ label, destructive }: { label: string; destructive?: boolean }) {
  return (
    <Tooltip>
      <TooltipTrigger asChild>
        <div>
          <DropdownMenuItem disabled variant={destructive ? 'destructive' : undefined} onSelect={(e) => e.preventDefault()}>
            {label}
          </DropdownMenuItem>
        </div>
      </TooltipTrigger>
      <TooltipContent side="left">Site managed application worker</TooltipContent>
    </Tooltip>
  );
}

function getColumns(sites?: Array<{ id: number; domain: string }>): ColumnDef<Worker>[] {
  return [
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
      accessorKey: 'site_id',
      header: 'Site',
      enableColumnFilter: true,
      enableSorting: true,
      cell: ({ row }) => {
        const siteId = row.original.site_id;
        if (!siteId) {
          return <span>-</span>;
        }
        const site = sites?.find((s) => s.id === siteId);
        return <span>{site ? site.domain : `Site #${siteId}`}</span>;
      },
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
        const worker = row.original;
        const locked = worker.is_site_bootstrap;
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
                {locked ? (
                  <BootstrapLockedItem label="Edit" />
                ) : (
                  <WorkerForm serverId={worker.server_id} worker={worker}>
                    <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Edit</DropdownMenuItem>
                  </WorkerForm>
                )}
                <WorkerAction type="start" worker={worker} />
                <WorkerAction type="stop" worker={worker} />
                <WorkerAction type="restart" worker={worker} />
                <WorkerLogs worker={worker} />
                <DropdownMenuSeparator />
                {locked ? <BootstrapLockedItem label="Delete" destructive /> : <Delete worker={worker} />}
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        );
      },
    },
  ];
}

export { getColumns as columns };
