import { ColumnDef } from '@tanstack/react-table';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { MoreVerticalIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import DateTime from '@/components/date-time';
import { SSL } from '@/types/ssl';
import moment from 'moment';
import { View } from '@/pages/server-logs/components/columns';
import ActivateServerSsl from '@/pages/server-ssls/components/activate-server-ssl';
import { useDialog } from '@/hooks/use-dialog';

function Delete({ ssl }: { ssl: SSL }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      variant="destructive"
      onSelect={() =>
        dialog.confirm.open({
          title: 'Delete SSL',
          description: 'Are you sure you want to delete this certificate?',
          variant: 'destructive',
          confirmLabel: 'Delete',
          method: 'delete',
          url: route('server-ssls.destroy', { server: ssl.server_id, ssl: ssl.id }),
        })
      }
    >
      Delete
    </DropdownMenuItem>
  );
}

export const columns: ColumnDef<SSL>[] = [
  {
    accessorKey: 'id',
    header: 'ID',
    enableColumnFilter: false,
    enableSorting: true,
  },
  {
    accessorKey: 'type',
    header: 'Type',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return <Badge variant="default">{row.original.type.toUpperCase()}</Badge>;
    },
  },
  {
    id: 'domain',
    header: 'Domain(s)',
    enableColumnFilter: true,
    enableSorting: false,
    cell: ({ row }) => {
      if (row.original.domains && row.original.domains.length > 0) {
        return (
          <div className="flex flex-wrap gap-1">
            <TooltipProvider>
              {row.original.domains.map((domain) => {
                const truncated = domain.length > 30;
                const label = truncated ? domain.slice(0, 30) + '...' : domain;
                return truncated ? (
                  <Tooltip key={domain}>
                    <TooltipTrigger asChild>
                      <Badge variant="outline" className="cursor-default">
                        {label}
                      </Badge>
                    </TooltipTrigger>
                    <TooltipContent>{domain}</TooltipContent>
                  </Tooltip>
                ) : (
                  <Badge key={domain} variant="outline">
                    {label}
                  </Badge>
                );
              })}
            </TooltipProvider>
          </div>
        );
      }
      return <span>-</span>;
    },
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
    accessorKey: 'expires_at',
    header: 'Expires in',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      if (!row.original.expires_at) {
        return <span>-</span>;
      }

      const targetDate = moment(row.original.expires_at);
      const today = moment();
      const daysRemaining = targetDate.diff(today, 'days');

      return (
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger asChild>
              <Badge variant="outline" className="cursor-default">
                {daysRemaining} {daysRemaining === 1 ? 'day' : 'days'}
              </Badge>
            </TooltipTrigger>
            <TooltipContent>{targetDate.format('MMMM D, YYYY')}</TooltipContent>
          </Tooltip>
        </TooltipProvider>
      );
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
              {row.original.has_csr && row.original.status === 'created' && (
                <>
                  <DropdownMenuItem
                    onSelect={() => window.open(route('server-ssls.download', { server: row.original.server_id, ssl: row.original.id }), '_blank')}
                  >
                    Download CSR
                  </DropdownMenuItem>
                  {row.original.type === 'csr' && <ActivateServerSsl ssl={row.original} />}
                  <DropdownMenuSeparator />
                </>
              )}
              {row.original.log && (
                <>
                  <View serverLog={row.original.log} label="View Log" />
                  <DropdownMenuSeparator />
                </>
              )}
              <Delete ssl={row.original} />
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];
