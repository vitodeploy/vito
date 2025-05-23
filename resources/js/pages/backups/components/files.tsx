import { Backup } from '@/types/backup';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import React, { ReactNode, useState } from 'react';
import { Button } from '@/components/ui/button';
import { LoaderCircle, LoaderCircleIcon, MoreVerticalIcon } from 'lucide-react';
import { useForm } from '@inertiajs/react';
import { BackupFile } from '@/types/backup-file';
import { ColumnDef } from '@tanstack/react-table';
import DateTime from '@/components/date-time';
import { Badge } from '@/components/ui/badge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { DataTable } from '@/components/data-table';
import axios from 'axios';
import { useQuery } from '@tanstack/react-query';
import { TableSkeleton } from '@/components/table-skeleton';
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

export default function BackupFiles({ backup, children }: { backup: Backup; children: ReactNode }) {
  const [open, setOpen] = useState(false);

  const fetchFilesQuery = useQuery({
    queryKey: ['fetchFiles'],
    queryFn: async () => {
      const res = await axios.get(
        route('backup-files', {
          server: backup.server_id,
          backup: backup.id,
        }),
      );
      return res.data;
    },
    enabled: open,
  });

  const runBackupForm = useForm();
  const runBackup = () => {
    runBackupForm.post(route('backups.run', { server: backup.server_id, backup: backup.id }), {
      onSuccess: () => {
        fetchFilesQuery.refetch();
      },
    });
  };

  const columns: ColumnDef<BackupFile>[] = [
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
      accessorKey: 'restored_to',
      header: 'Restored to',
      enableColumnFilter: true,
      enableSorting: true,
    },
    {
      accessorKey: 'restored_at',
      header: 'Restored at',
      enableColumnFilter: true,
      enableSorting: true,
      cell: ({ row }) => {
        return row.original.restored_at ? <DateTime date={row.original.restored_at} /> : '';
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
                <RestoreBackup backup={backup} file={row.original} onBackupRestored={() => fetchFilesQuery.refetch()}>
                  <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Restore</DropdownMenuItem>
                </RestoreBackup>
                <Delete file={row.original} onDeleted={() => fetchFilesQuery.refetch()} />
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        );
      },
    },
  ];

  return (
    <Sheet open={open} onOpenChange={setOpen}>
      <SheetTrigger asChild>{children}</SheetTrigger>
      <SheetContent className="sm:max-w-4xl">
        <SheetHeader>
          <SheetTitle>Backup files of [{backup.database.name}]</SheetTitle>
          <SheetDescription className="sr-only">Backup files</SheetDescription>
        </SheetHeader>
        {fetchFilesQuery.isLoading && <TableSkeleton modal />}
        {fetchFilesQuery.isSuccess && !fetchFilesQuery.isLoading && <DataTable columns={columns} data={fetchFilesQuery.data.data} modal />}
        <SheetFooter>
          <div className="flex items-center gap-2">
            <Button type="button" onClick={runBackup} disabled={runBackupForm.processing}>
              {runBackupForm.processing && <LoaderCircle className="animate-spin" />}
              Run backup
            </Button>
            <SheetClose asChild>
              <Button variant="outline">Close</Button>
            </SheetClose>
          </div>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}
