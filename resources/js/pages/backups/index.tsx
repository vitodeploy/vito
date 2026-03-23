import { Head, Link, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import ServerLayout from '@/layouts/server/layout';
import { BookOpenIcon, LoaderCircleIcon, MoreVerticalIcon, PlusIcon } from 'lucide-react';
import { Backup } from '@/types/backup';
import { DynamicTable } from '@/components/dynamic-table';
import { DynamicTableData } from '@/types/dynamic-table';
import CreateBackup from '@/pages/backups/components/create-backup';
import EditBackup from '@/pages/backups/components/edit-backup';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
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
import { useForm } from '@inertiajs/react';
import FormSuccessful from '@/components/form-successful';
import { useState } from 'react';

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
          <DialogTitle>Delete backup [{backup.type === 'database' ? String(backup.database_name) : String(backup.path)}]</DialogTitle>
          <DialogDescription className="sr-only">Delete backup</DialogDescription>
        </DialogHeader>
        <p className="p-4">
          Are you sure you want to delete this backup:{' '}
          <strong>{backup.type === 'database' ? String(backup.database_name) : String(backup.path)}</strong>? All backup files will be deleted and
          this action cannot be undone.
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

type Page = {
  server: Server;
  backups: DynamicTableData;
};

export default function Backups() {
  const page = usePage<Page>();

  return (
    <ServerLayout>
      <Head title={`Backups - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Backups" description="Here you can manage database and file backups" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/database#backup" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <CreateBackup server={page.props.server}>
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">Create</span>
              </Button>
            </CreateBackup>
          </div>
        </HeaderContainer>

        <DynamicTable
          tableData={page.props.backups}
          realtimeEvent="backup"
          actions={(backup) => (
            <div className="flex items-center justify-end">
              <DropdownMenu modal={false}>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" className="h-8 w-8 p-0">
                    <span className="sr-only">Open menu</span>
                    <MoreVerticalIcon />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <EditBackup backup={backup as unknown as Backup}>
                    <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Edit</DropdownMenuItem>
                  </EditBackup>
                  <Link href={route('backup-files', { server: (backup as unknown as Backup).server_id, backup: (backup as unknown as Backup).id })}>
                    <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Files</DropdownMenuItem>
                  </Link>
                  <DropdownMenuSeparator />
                  <Delete backup={backup as unknown as Backup} />
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          )}
        />
      </Container>
    </ServerLayout>
  );
}
