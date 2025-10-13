import { ColumnDef } from '@tanstack/react-table';
import { Workflow } from '@/types/workflow';
import DateTime from '@/components/date-time';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { MoreVerticalIcon } from 'lucide-react';
import Run from './run';
import { router } from '@inertiajs/react';
import DeleteWorkflow from './delete-workflow';

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
        <div className="flex items-center justify-end">
          <DropdownMenu modal={true}>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                <span className="sr-only">Open menu</span>
                <MoreVerticalIcon />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <Run workflow={row.original}>
                <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Run</DropdownMenuItem>
              </Run>
              <DropdownMenuItem onSelect={() => router.visit(route('workflow-runs', { workflow: row.original.id }))}>History</DropdownMenuItem>
              <DropdownMenuItem onSelect={() => router.visit(route('workflows.show', { workflow: row.original.id }))}>Edit</DropdownMenuItem>
              <DropdownMenuSeparator />
              <DeleteWorkflow workflow={row.original}>
                <DropdownMenuItem onSelect={(e) => e.preventDefault()} className="text-destructive focus:text-destructive">
                  Delete
                </DropdownMenuItem>
              </DeleteWorkflow>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];
