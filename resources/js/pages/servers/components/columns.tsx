'use client';

import { ColumnDef } from '@tanstack/react-table';
import { Server } from '@/types/server';
import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { EyeIcon } from 'lucide-react';
import ServerStatus from '@/pages/servers/components/status';

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
          <Link href={route('servers.show', { server: row.original.id })}>
            <Button variant="outline" size="sm">
              <EyeIcon />
            </Button>
          </Link>
        </div>
      );
    },
  },
];
