import { ColumnDef } from '@tanstack/react-table';
import { Workflow } from '@/types/workflow';
import DateTime from '@/components/date-time';
import { Button } from '@/components/ui/button';
import { PlayIcon } from 'lucide-react';
import Run from './run';

export const columns: ColumnDef<Workflow>[] = [
  {
    accessorKey: 'name',
    header: 'Name',
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
    accessorKey: 'updated_at',
    header: 'Updated at',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return <DateTime date={row.original.updated_at} />;
    },
  },
  {
    id: 'actions',
    enableColumnFilter: false,
    enableSorting: false,
    cell: ({ row }) => {
      return (
        <div className="flex items-center justify-end" onClick={(e) => e.stopPropagation()}>
          <Run workflow={row.original}>
            <Button variant="outline" className="hover:text-success size-7">
              <PlayIcon />
            </Button>
          </Run>
        </div>
      );
    },
  },
];
