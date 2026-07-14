import { Button } from '@/components/ui/button';
import { LoaderCircleIcon, MoreVerticalIcon } from 'lucide-react';
import { BackupFile } from '@/types/backup-file';
import { ColumnDef } from '@tanstack/react-table';
import DateTime from '@/components/date-time';
import { Badge } from '@/components/ui/badge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import CopyableBadge from '@/components/copyable-badge';
import { useDialog } from '@/hooks/use-dialog';
import { Backup } from '@/types/backup';
import ErrorIndicator from '@/components/error-indicator';
import { formatBytes } from '@/lib/utils';

function Restore({ backup, file }: { backup: Backup; file: BackupFile }) {
  const dialog = useDialog();

  return <DropdownMenuItem onSelect={() => dialog.backupRestore.open({ backup, file })}>Restore</DropdownMenuItem>;
}

function Delete({ file }: { file: BackupFile }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      variant="destructive"
      onSelect={() =>
        dialog.confirm.open({
          title: 'Delete backup file',
          description: 'Are you sure you want to delete this backup file?',
          variant: 'destructive',
          confirmLabel: 'Delete',
          method: 'delete',
          url: route('backup-files.destroy', { server: file.server_id, backup: file.backup_id, backupFile: file.id }),
        })
      }
    >
      Delete
    </DropdownMenuItem>
  );
}

export const columns: ColumnDef<BackupFile>[] = [
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
    accessorKey: 'database_engine',
    header: 'Source',
    enableColumnFilter: true,
    enableSorting: false,
    cell: ({ row }) => {
      return row.original.database_engine ? (
        <Badge variant="outline">{`${row.original.database_engine} ${row.original.database_version ?? ''}`.trim()}</Badge>
      ) : (
        '-'
      );
    },
  },
  {
    accessorKey: 'size',
    header: 'Size',
    enableColumnFilter: false,
    enableSorting: false,
    cell: ({ row }) => {
      return row.original.size === null ? '-' : formatBytes(row.original.size, 2);
    },
  },
  {
    accessorKey: 'restored_to',
    header: 'Restored to',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return row.original.restored_to ? <CopyableBadge text={row.original.restored_to} tooltip /> : '-';
    },
  },
  {
    accessorKey: 'restored_at',
    header: 'Restored at',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return row.original.restored_at ? <DateTime date={row.original.restored_at} /> : '-';
    },
  },
  {
    accessorKey: 'status',
    header: 'Status',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return (
        <div className="flex items-center gap-1.5">
          <Badge variant={row.original.status_color}>{row.original.status}</Badge>
          <ErrorIndicator error={row.original.message} label={`Backup file "${row.original.name}" error`} />
        </div>
      );
    },
  },
  {
    id: 'actions',
    enableColumnFilter: false,
    enableSorting: false,
    cell: ({ row }) => {
      if (row.original.status === 'creating' || row.original.status === 'deleting') {
        return (
          <div className="flex items-center justify-end">
            <span
              className="flex h-8 w-8 items-center justify-center"
              aria-label={row.original.status === 'deleting' ? 'Deleting backup file' : 'Creating backup file'}
            >
              <LoaderCircleIcon className="text-muted-foreground h-4 w-4 animate-spin" />
            </span>
          </div>
        );
      }

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
              <Restore backup={row.original.backup} file={row.original} />
              <Delete file={row.original} />
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];
