'use client';

import { ColumnDef } from '@tanstack/react-table';
import { Button } from '@/components/ui/button';
import { EyeIcon } from 'lucide-react';
import type { ServerLog } from '@/types/server-log';

export const columns: ColumnDef<ServerLog>[] = [
  {
    accessorKey: 'name',
    header: 'Event',
    enableColumnFilter: true,
  },
  {
    accessorKey: 'created_at_by_timezone',
    header: 'Created At',
    enableSorting: true,
  },
  {
    id: 'actions',
    enableColumnFilter: false,
    enableSorting: false,
    cell: ({ row }) => {
      return (
        <div className="flex items-center justify-end">
          <Button variant="outline" size="sm">
            <EyeIcon />
          </Button>
        </div>
      );
    },
  },
];
