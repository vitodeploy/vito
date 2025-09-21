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
import { CronJob } from '@/types/cronjob';
import { Badge } from '@/components/ui/badge';
import DateTime from '@/components/date-time';
import CronJobForm from '@/pages/cronjobs/components/form';
import CopyableBadge from '@/components/copyable-badge';
import { Site } from '@/types/site';

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

function getColumns(site?: Site, sites?: Array<{ id: number; domain: string }>): ColumnDef<CronJob>[] {
  return [
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
      accessorKey: 'frequency',
      header: 'Frequency',
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
                <CronJobForm serverId={row.original.server_id} site={site} cronJob={row.original}>
                  <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Edit</DropdownMenuItem>
                </CronJobForm>
                {row.original.status === 'disabled' && <Action type="enable" cronJob={row.original} site={site} />}
                {row.original.status === 'ready' && <Action type="disable" cronJob={row.original} site={site} />}
                <DropdownMenuSeparator />
                <Delete cronJob={row.original} site={site} />
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        );
      },
    },
  ];
}

export { getColumns as columns };
