import { ColumnDef } from '@tanstack/react-table';
import { Button } from '@/components/ui/button';
import { MoreVerticalIcon } from 'lucide-react';
import DateTime from '@/components/date-time';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Deployment } from '@/types/deployment';
import { Badge } from '@/components/ui/badge';
import { Download, View } from '@/pages/server-logs/components/columns';
import { DropdownMenuSeparator } from '@radix-ui/react-dropdown-menu';
import Rollback from './rollback';

export const columns: ColumnDef<Deployment>[] = [
  {
    accessorKey: 'id',
    header: 'ID',
    enableColumnFilter: true,
  },
  {
    accessorKey: 'commit_id',
    header: 'Commit',
    enableColumnFilter: true,
    cell: ({ row }) => {
      return row.original.commit_data?.message ? (
        <a href={row.original.commit_data?.url} target="_blank" className="text-primary inline-flex truncate font-mono">
          <span className="block max-w-[200px] overflow-x-hidden overflow-ellipsis">{row.original.commit_data.message}</span>
        </a>
      ) : (
        'No message'
      );
    },
  },
  {
    accessorKey: 'created_at',
    header: 'Deployed At',
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
    accessorKey: 'release',
    header: 'Release',
    enableColumnFilter: true,
    cell: ({ row }) => {
      return (
        <div className="inline-flex items-center gap-2">
          {row.original.release}
          {row.original.active && <Badge variant="success">active</Badge>}
        </div>
      );
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
              <View serverLog={row.original.log} />
              <Download serverLog={row.original.log}>
                <DropdownMenuItem>Download</DropdownMenuItem>
              </Download>
              <DropdownMenuSeparator />
              <Rollback deployment={row.original}>
                <DropdownMenuItem variant="destructive" onSelect={(e) => e.preventDefault()}>
                  Rollback
                </DropdownMenuItem>
              </Rollback>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];
