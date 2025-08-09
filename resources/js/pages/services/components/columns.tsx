import { ColumnDef } from '@tanstack/react-table';
import { DropdownMenu, DropdownMenuContent, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { MoreVerticalIcon } from 'lucide-react';
import { Service } from '@/types/service';
import { Badge } from '@/components/ui/badge';
import DateTime from '@/components/date-time';
import Uninstall from '@/pages/services/components/uninstall';
import { Action } from '@/pages/services/components/action';
import Version from './version';

export const columns: ColumnDef<Service>[] = [
  {
    accessorKey: 'name',
    header: 'Name',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    accessorKey: 'version',
    header: 'Version',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return <Version service={row.original} />;
    },
  },
  {
    accessorKey: 'created_at',
    header: 'Installed at',
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
              <Action type="start" service={row.original} />
              <Action type="stop" service={row.original} />
              <Action type="restart" service={row.original} />
              <Action type="enable" service={row.original} />
              <Action type="disable" service={row.original} />
              <DropdownMenuSeparator />
              <Uninstall service={row.original} />
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];
