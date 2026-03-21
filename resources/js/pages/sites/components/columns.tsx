import { ColumnDef } from '@tanstack/react-table';
import { Server } from '@/types/server';
import { Link } from '@inertiajs/react';
import DateTime from '@/components/date-time';
import { Site } from '@/types/site';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { EyeIcon, TriangleAlertIcon } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

export default function getColumns(server?: Server): ColumnDef<Site>[] {
  let columns: ColumnDef<Site>[] = [
    {
      accessorKey: 'id',
      header: 'ID',
      enableColumnFilter: true,
      enableSorting: true,
      enableHiding: true,
    },
    {
      accessorKey: 'domain',
      header: 'Domain',
      enableColumnFilter: true,
      enableSorting: true,
    },
    {
      accessorKey: 'type',
      header: 'Type',
      enableColumnFilter: true,
      enableSorting: true,
      cell: ({ row }) => {
        return <Badge variant="outline">{row.original.type}</Badge>;
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
        const count = row.original.warnings?.length ?? 0;
        return (
          <div className="flex items-center justify-end gap-2">
            {count > 0 && (
              <TooltipProvider delayDuration={0}>
                <Tooltip>
                  <TooltipTrigger asChild>
                    <div className="bg-warning/15 text-warning border-warning/40 flex cursor-default items-center gap-1.5 rounded-md border px-2 py-1.5">
                      <TriangleAlertIcon className="h-4 w-4" />
                      <span className="text-xs font-semibold">{count}</span>
                    </div>
                  </TooltipTrigger>
                  <TooltipContent>
                    {count} {count === 1 ? 'warning' : 'warnings'}
                  </TooltipContent>
                </Tooltip>
              </TooltipProvider>
            )}
            <Link href={route('application', { server: row.original.server_id, site: row.original.id })} prefetch>
              <Button variant="outline" size="sm">
                <EyeIcon />
              </Button>
            </Link>
          </div>
        );
      },
    },
  ];

  if (!server) {
    // add column to the first
    columns = [
      {
        id: 'server',
        header: 'Server',
        cell: ({ row }) => {
          return (
            <Link href={route('servers.show', { server: row.original.server_id })} prefetch>
              {row.original.server?.name}
            </Link>
          );
        },
      },
      ...columns,
    ];
  }

  return columns;
}
