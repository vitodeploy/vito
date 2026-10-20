import { ColumnDef } from '@tanstack/react-table';
import { Link } from '@inertiajs/react';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { LockKeyholeIcon, MoreVerticalIcon } from 'lucide-react';
import { CronJob } from '@/types/cronjob';
import { Badge } from '@/components/ui/badge';
import DateTime from '@/components/date-time';
import CopyableBadge from '@/components/copyable-badge';
import { Site } from '@/types/site';
import { useDialog } from '@/hooks/use-dialog';

function Action({ type, cronJob, site }: { type: 'enable' | 'disable'; cronJob: CronJob; site?: Site }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem onSelect={() => dialog.confirm.open({
      title: `${type === 'enable' ? 'Enable' : 'Disable'} cron job`,
      description: `Are you sure you want to ${type} this cron job?`,
      method: 'post',
      url: `${cronJob.component_url ?? `/servers/${cronJob.server_id}${site ? `/sites/${site.id}` : ''}`}/cronjobs/${cronJob.id}/${type}`,
    })}>
      {type === 'enable' ? 'Enable' : 'Disable'}
    </DropdownMenuItem>
  );
}

function Delete({ cronJob, site }: { cronJob: CronJob; site?: Site }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      variant="destructive"
      onSelect={() =>
        dialog.confirm.open({
          title: 'Delete cron job',
          description: 'Are you sure you want to delete this cron job? This action cannot be undone.',
          variant: 'destructive',
          confirmLabel: 'Delete',
          method: 'delete',
          url: `${cronJob.component_url ?? `/servers/${cronJob.server_id}${site ? `/sites/${site.id}` : ''}`}/cronjobs/${cronJob.id}`,
        })
      }
    >
      Delete
    </DropdownMenuItem>
  );
}

export function Actions({ cronJob, site, canManage = true }: { cronJob: CronJob; site?: Site; canManage?: boolean }) {
  const dialog = useDialog();

  if (cronJob.managed) return <span className="text-muted-foreground flex items-center justify-end gap-2 text-sm"><LockKeyholeIcon className="size-4" />Capability managed</span>;
  if (!canManage) return null;

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
          <DropdownMenuItem onSelect={() => dialog.cronjobForm.open({ serverId: cronJob.server_id, site, cronJob })}>Edit</DropdownMenuItem>
          {cronJob.status === 'disabled' && <Action type="enable" cronJob={cronJob} site={site} />}
          {cronJob.status === 'ready' && <Action type="disable" cronJob={cronJob} site={site} />}
          <DropdownMenuSeparator />
          <Delete cronJob={cronJob} site={site} />
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  );
}

function getColumns(site?: Site, sites?: Array<{ id: number; domain: string }>): ColumnDef<CronJob>[] {
  return [
    {
      accessorKey: 'name',
      header: 'Name',
      enableColumnFilter: true,
      enableSorting: true,
      cell: ({ row }) => {
        return <span>{row.original.name || <i className="text-muted-foreground">No name</i>}</span>;
      },
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
      accessorKey: 'component_name',
      header: 'Component',
      enableColumnFilter: true,
      enableSorting: true,
      cell: ({ row }) => row.original.component_url
        ? <Link href={row.original.component_url} className="hover:underline">{row.original.component_name ?? `Component #${row.original.component_id}`}</Link>
        : <span>-</span>,
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
        return <Actions cronJob={row.original} site={site} />;
      },
    },
  ];
}

export { getColumns as columns };
