import { ColumnDef } from '@tanstack/react-table';
import { Button } from '@/components/ui/button';
import { MoreVerticalIcon } from 'lucide-react';
import DateTime from '@/components/date-time';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { ScriptExecution } from '@/types/script-execution';
import { Badge } from '@/components/ui/badge';
import { Download, View } from '@/pages/server-logs/components/columns';
import { Link } from '@inertiajs/react';

export const columns: ColumnDef<ScriptExecution>[] = [
  {
    accessorKey: 'created_at',
    header: 'Deployed At',
    enableSorting: true,
    cell: ({ row }) => {
      return <DateTime date={row.original.created_at} />;
    },
  },
  {
    accessorKey: 'server',
    header: 'Server',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return row.original.server ? (
        <Link href={`/servers/${row.original.server_id}`} className="hover:underline">
          {row.original.server.name} ({row.original.server.ip})
        </Link>
      ) : (
        '-'
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
              <View serverLog={row.original.log} />
              <Download serverLog={row.original.log}>
                <DropdownMenuItem>Download</DropdownMenuItem>
              </Download>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];
