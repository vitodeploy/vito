import { ColumnDef } from '@tanstack/react-table';
import DateTime from '@/components/date-time';
import { WorkflowRun } from '@/types/workflow-run';
import { Badge } from '@/components/ui/badge';

export const columns: ColumnDef<WorkflowRun>[] = [
  {
    accessorKey: 'id',
    header: 'ID',
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
      return <Badge variant={row.original.status_color}>{row.original.status}</Badge>;
    },
  },
];
