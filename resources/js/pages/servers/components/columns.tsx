import { ColumnDef } from '@tanstack/react-table';
import { Server } from '@/types/server';
import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { EyeIcon } from 'lucide-react';
import ServerStatus from '@/pages/servers/components/status';
import DateTime from '@/components/date-time';

export const columns: ColumnDef<Server>[] = [
  {
    accessorKey: 'id',
    header: 'ID',
    enableColumnFilter: true,
    enableSorting: true,
    enableHiding: true,
  },
  {
    accessorKey: 'name',
    header: 'Name',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    accessorKey: 'ip',
    header: 'IP',
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
      return <ServerStatus server={row.original} />;
    },
  },
  {
    id: 'actions',
    enableColumnFilter: false,
    enableSorting: false,
    cell: ({ row }) => {
      return (
        <div className="flex items-center justify-end">
          <Link href={route('servers.show', { server: row.original.id })} prefetch>
            <Button variant="outline" size="sm">
              <EyeIcon />
            </Button>
          </Link>
        </div>
      );
    },
  },
];
