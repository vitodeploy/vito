import { ColumnDef } from '@tanstack/react-table';
import DateTime from '@/components/date-time';
import { Domain } from '@/types/domain';
import { Badge } from '@/components/ui/badge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';
import { MoreVerticalIcon } from 'lucide-react';
import { useDialog } from '@/hooks/use-dialog';

function Remove({ domain }: { domain: Domain }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      variant="destructive"
      onSelect={() =>
        dialog.confirm.open({
          title: `Remove ${domain.domain}`,
          description: `Are you sure you want to remove ${domain.domain} from Vito? This will only remove the domain from Vito, not from your DNS provider.`,
          variant: 'destructive',
          confirmLabel: 'Remove',
          method: 'delete',
          url: route('domains.destroy', domain.id),
        })
      }
    >
      Remove
    </DropdownMenuItem>
  );
}

export const columns: ColumnDef<Domain>[] = [
  {
    accessorKey: 'id',
    header: 'ID',
    enableColumnFilter: true,
    enableSorting: true,
    enableHiding: true,
  },
  {
    accessorKey: 'domain',
    header: 'Domain',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    accessorKey: 'dns_provider.name',
    header: 'DNS Provider',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      const dnsProvider = row.original.dns_provider;
      return (
        <div className="flex items-center gap-2">
          <span>{dnsProvider?.name}</span>
          <Badge variant="outline">{dnsProvider?.provider}</Badge>
        </div>
      );
    },
  },
  {
    accessorKey: 'created_at',
    header: 'Added at',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return <DateTime date={row.original.created_at} />;
    },
  },
  {
    id: 'actions',
    enableColumnFilter: false,
    enableSorting: false,
    cell: ({ row }) => {
      return (
        <div className="flex items-center justify-end gap-2">
          <DropdownMenu modal={false}>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                <span className="sr-only">Open menu</span>
                <MoreVerticalIcon />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem onSelect={() => router.visit(route('domains.show', row.original.id))}>Manage Records</DropdownMenuItem>
              <DropdownMenuSeparator />
              <Remove domain={row.original} />
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];
