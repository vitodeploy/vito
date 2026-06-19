import { ColumnDef } from '@tanstack/react-table';
import DateTime from '@/components/date-time';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { Link, useForm } from '@inertiajs/react';
import { LoaderCircleIcon, MoreVerticalIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Backup } from '@/types/backup';
import CopyableBadge from '@/components/copyable-badge';
import { useDialog } from '@/hooks/use-dialog';
import ErrorIndicator from '@/components/error-indicator';

function Edit({ backup }: { backup: Backup }) {
  const dialog = useDialog();

  return <DropdownMenuItem onSelect={() => dialog.backupEdit.open({ backup })}>Edit</DropdownMenuItem>;
}

function ToggleEnabled({ backup }: { backup: Backup }) {
  const form = useForm();

  const submit = () => {
    form.post(route(backup.enabled ? 'backups.disable' : 'backups.enable', { server: backup.server_id, backup: backup.id }), {
      preserveScroll: true,
    });
  };

  return (
    <DropdownMenuItem onClick={submit} disabled={form.processing}>
      {backup.enabled ? 'Disable' : 'Enable'}
    </DropdownMenuItem>
  );
}

function Delete({ backup }: { backup: Backup }) {
  const dialog = useDialog();
  const target = (backup.type === 'database' ? backup.database?.name : backup.path) ?? `#${backup.id}`;

  return (
    <DropdownMenuItem
      variant="destructive"
      onSelect={() =>
        dialog.confirm.open({
          title: `Delete backup [${target}]`,
          description: `Are you sure you want to delete this backup: ${target}? All backup files will be deleted and this action cannot be undone.`,
          variant: 'destructive',
          confirmLabel: 'Delete',
          method: 'delete',
          url: route('backups.destroy', { server: backup.server_id, backup: backup.id }),
        })
      }
    >
      Delete
    </DropdownMenuItem>
  );
}

export const columns: ColumnDef<Backup>[] = [
  {
    accessorKey: 'type',
    header: 'Type',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    accessorKey: 'target',
    header: 'Target',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      const backup = row.original;
      return <CopyableBadge text={backup.type === 'database' ? backup.database?.name : backup.path} tooltip />;
    },
  },
  {
    accessorKey: 'storage_id',
    header: 'Storage',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return <span>{row.original.storage.name}</span>;
    },
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
    accessorKey: 'last_file',
    header: 'Last file status',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return (
        row.original.last_file && (
          <div className="flex items-center gap-1.5">
            <Badge variant={row.original.last_file.status_color}>{row.original.last_file.status}</Badge>
            <ErrorIndicator error={row.original.last_file.message} label={`Backup #${row.original.id} last file error`} />
          </div>
        )
      );
    },
  },
  {
    accessorKey: 'status',
    header: 'Status',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      if (row.original.status) {
        return <Badge variant={row.original.status_color ?? 'gray'}>{row.original.status}</Badge>;
      }

      return row.original.enabled ? <Badge variant="success">enabled</Badge> : <Badge variant="destructive">disabled</Badge>;
    },
  },
  {
    id: 'actions',
    enableColumnFilter: false,
    enableSorting: false,
    cell: ({ row }) => {
      return (
        <div className="flex items-center justify-end gap-1.5">
          {row.original.status === 'deleting' ? (
            <span className="flex h-8 w-8 items-center justify-center" aria-label="Deleting backup">
              <LoaderCircleIcon className="text-muted-foreground h-4 w-4 animate-spin" />
            </span>
          ) : (
            <DropdownMenu modal={false}>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" className="h-8 w-8 p-0">
                  <span className="sr-only">Open menu</span>
                  <MoreVerticalIcon />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <Edit backup={row.original} />
                <ToggleEnabled backup={row.original} />
                <Link href={route('backup-files', { server: row.original.server_id, backup: row.original.id })}>
                  <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Files</DropdownMenuItem>
                </Link>
                <DropdownMenuSeparator />
                <Delete backup={row.original} />
              </DropdownMenuContent>
            </DropdownMenu>
          )}
        </div>
      );
    },
  },
];
