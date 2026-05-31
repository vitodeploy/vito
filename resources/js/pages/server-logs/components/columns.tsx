import { ColumnDef } from '@tanstack/react-table';
import { MoreVerticalIcon } from 'lucide-react';
import type { ServerLog } from '@/types/server-log';
import { ReactNode } from 'react';
import DateTime from '@/components/date-time';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useDialog } from '@/hooks/use-dialog';

export function View({ serverLog, label = 'View' }: { serverLog: ServerLog; label?: string }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem onSelect={() => dialog.logViewer.open({ serverId: serverLog.server_id, logId: serverLog.id, title: label })}>
      {label}
    </DropdownMenuItem>
  );
}

export function Download({ serverLog, children }: { serverLog: ServerLog; children: ReactNode }) {
  return (
    <a href={route('logs.download', { server: serverLog.server_id, log: serverLog.id })} target="_blank">
      {children}
    </a>
  );
}

function Clear({ serverLog }: { serverLog: ServerLog }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      onSelect={() =>
        dialog.confirm.open({
          title: `Clear ${serverLog.name}`,
          description: `Are you sure you want to clear the contents of ${serverLog.name}? This will remove all content from the log file but keep the file itself.`,
          confirmLabel: 'Clear',
          method: 'post',
          url: route('logs.clear', { server: serverLog.server_id, log: serverLog.id }),
        })
      }
    >
      Clear
    </DropdownMenuItem>
  );
}

function Delete({ serverLog }: { serverLog: ServerLog }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      variant="destructive"
      onSelect={() =>
        dialog.confirm.open({
          title: `Delete ${serverLog.name}`,
          description: `Are you sure you want to delete ${serverLog.name}?`,
          variant: 'destructive',
          confirmLabel: 'Delete',
          method: 'delete',
          url: route('logs.destroy', { server: serverLog.server_id, log: serverLog.id }),
        })
      }
    >
      Delete
    </DropdownMenuItem>
  );
}

export const columns: ColumnDef<ServerLog>[] = [
  {
    accessorKey: 'name',
    header: 'Event',
    enableColumnFilter: true,
  },
  {
    accessorKey: 'created_at',
    header: 'Created At',
    enableSorting: true,
    cell: ({ row }) => {
      return <DateTime date={row.original.created_at} />;
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
              <View serverLog={row.original} />
              <Download serverLog={row.original}>
                <DropdownMenuItem>Download</DropdownMenuItem>
              </Download>
              <DropdownMenuSeparator />
              {row.original.is_remote && <Clear serverLog={row.original} />}
              <Delete serverLog={row.original} />
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];
