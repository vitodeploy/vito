import { ColumnDef } from '@tanstack/react-table';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { MoreVerticalIcon } from 'lucide-react';
import { Worker } from '@/types/worker';
import { Badge } from '@/components/ui/badge';
import DateTime from '@/components/date-time';
import CopyableBadge from '@/components/copyable-badge';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { WorkerAction, WorkerEnvironment, WorkerLogs } from '@/pages/workers/components/worker-row-actions';
import ErrorIndicator from '@/components/error-indicator';
import { useDialog } from '@/hooks/use-dialog';

function Delete({ worker }: { worker: Worker }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      variant="destructive"
      onSelect={() =>
        dialog.confirm.open({
          title: 'Delete worker',
          description: 'Are you sure you want to delete this worker? This action cannot be undone.',
          variant: 'destructive',
          confirmLabel: 'Delete',
          method: 'delete',
          url: route('workers.destroy', { server: worker.server_id, worker: worker }),
        })
      }
    >
      Delete
    </DropdownMenuItem>
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

function Actions({ worker }: { worker: Worker }) {
  const dialog = useDialog();
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
            <DropdownMenuItem onSelect={() => dialog.workerForm.open({ serverId: worker.server_id, worker })}>Edit</DropdownMenuItem>
          )}
          <WorkerAction type="start" worker={worker} />
          <WorkerAction type="stop" worker={worker} />
          <WorkerAction type="restart" worker={worker} />
          <WorkerLogs worker={worker} />
          <WorkerEnvironment worker={worker} />
          <DropdownMenuSeparator />
          {locked ? <BootstrapLockedItem label="Delete" destructive /> : <Delete worker={worker} />}
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
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
        return (
          <div className="flex items-center gap-1.5">
            <Badge variant={row.original.status_color}>{row.original.status}</Badge>
            <ErrorIndicator error={row.original.error} label={`Worker "${row.original.name}" error`} />
          </div>
        );
      },
    },
    {
      id: 'actions',
      enableColumnFilter: false,
      enableSorting: false,
      cell: ({ row }) => {
        return <Actions worker={row.original} />;
      },
    },
  ];
}

export { getColumns as columns };
