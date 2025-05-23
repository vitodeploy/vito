import { ColumnDef } from '@tanstack/react-table';
import DateTime from '@/components/date-time';
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
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon, MoreVerticalIcon } from 'lucide-react';
import FormSuccessful from '@/components/form-successful';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Backup } from '@/types/backup';
import BackupFiles from '@/pages/backups/components/files';
import EditBackup from '@/pages/backups/components/edit-backup';

function Delete({ backup }: { backup: Backup }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.delete(route('backups.destroy', { server: backup.server_id, backup: backup.id }), {
      onSuccess: () => {
        setOpen(false);
      },
    });
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
          <DialogTitle>Delete backup [{backup.database.name}]</DialogTitle>
          <DialogDescription className="sr-only">Delete backup</DialogDescription>
        </DialogHeader>
        <p className="p-4">
          Are you sure you want to this backup: <strong>{backup.database.name}</strong>? All backup files will be deleted and this action cannot be
          undone.
        </p>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button variant="destructive" disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Delete
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

export const columns: ColumnDef<Backup>[] = [
  {
    accessorKey: 'database_id',
    header: 'Database',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return <span>{row.original.database.name}</span>;
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
    accessorKey: 'status',
    header: 'Status',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return <Badge variant={row.original.status_color}>{row.original.status}</Badge>;
    },
  },
  {
    accessorKey: 'last_file',
    header: 'Last file status',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return row.original.last_file && <Badge variant={row.original.last_file.status_color}>{row.original.last_file.status}</Badge>;
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
              <EditBackup backup={row.original}>
                <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Edit</DropdownMenuItem>
              </EditBackup>
              <BackupFiles backup={row.original}>
                <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Files</DropdownMenuItem>
              </BackupFiles>
              <DropdownMenuSeparator />
              <Delete backup={row.original} />
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];
