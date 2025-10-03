import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon, MoreVerticalIcon } from 'lucide-react';
import { useForm } from '@inertiajs/react';
import { BackupFile } from '@/types/backup-file';
import { ColumnDef } from '@tanstack/react-table';
import DateTime from '@/components/date-time';
import { Badge } from '@/components/ui/badge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import RestoreBackup from '@/pages/backups/components/restore-backup';
import CopyableBadge from '@/components/copyable-badge';

function Delete({ file, onDeleted }: { file: BackupFile; onDeleted?: (file: BackupFile) => void }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.delete(
      route('backup-files.destroy', {
        server: file.server_id,
        backup: file.backup_id,
        backupFile: file.id,
      }),
      {
        onSuccess: () => {
          setOpen(false);
          if (onDeleted) {
            onDeleted(file);
          }
        },
      },
    );
  };
  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem variant="destructive" onSelect={(e) => e.preventDefault()}>
          Delete
        </DropdownMenuItem>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete backup file</DialogTitle>
          <DialogDescription className="sr-only">Delete backup file</DialogDescription>
        </DialogHeader>
        <p className="p-4">Are you sure you want to this backup file?</p>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button variant="destructive" disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Delete
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
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
              <RestoreBackup backup={row.original.backup} file={row.original}>
                <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Restore</DropdownMenuItem>
              </RestoreBackup>
              <Delete file={row.original} />
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];
