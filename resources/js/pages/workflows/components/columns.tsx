import { ColumnDef } from '@tanstack/react-table';
import { Workflow } from '@/types/workflow';
import { Badge } from '@/components/ui/badge';

export const columns: ColumnDef<Workflow>[] = [
  {
    accessorKey: 'name',
    header: 'Name',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    accessorKey: 'is_draft',
    header: 'Status',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return <Badge variant={row.original.is_draft ? 'outline' : 'default'}>{row.original.is_draft ? 'Draft' : 'Published'}</Badge>;
    },
  },
];
