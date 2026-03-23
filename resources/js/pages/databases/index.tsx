import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import type { Database } from '@/types/database';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import CreateDatabase from '@/pages/databases/components/create-database';
import { Button } from '@/components/ui/button';
import ServerLayout from '@/layouts/server/layout';
import { DynamicTable } from '@/components/dynamic-table';
import { DynamicTableData } from '@/types/dynamic-table';
import { BookOpenIcon, LoaderCircleIcon, MoreVerticalIcon, PlusIcon } from 'lucide-react';
import SyncDatabases from '@/pages/databases/components/sync-databases';
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
import { useForm } from '@inertiajs/react';
import FormSuccessful from '@/components/form-successful';
import { useState } from 'react';

function Delete({ database }: { database: Database }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.delete(route('databases.destroy', { server: database.server_id, database: database }), {
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
          <DialogTitle>Delete database [{database.name}]</DialogTitle>
          <DialogDescription className="sr-only">Delete database</DialogDescription>
        </DialogHeader>
        <p className="p-4">
          Are you sure you want to delete database <strong>{database.name}</strong>? This action cannot be undone.
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
  databases: DynamicTableData;
};

export default function Databases() {
  const page = usePage<Page>();

  const dbType = page.props.server.services['database'];
  const defaultCharset = dbType === 'postgresql' ? 'UTF8' : 'utf8mb4';
  const defaultCollation = dbType === 'postgresql' ? 'C.utf8' : 'utf8mb4_0900_ai_ci';

  return (
    <ServerLayout>
      <Head title={`Databases - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Databases" description="Here you can manage the databases" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/database" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <SyncDatabases server={page.props.server} />
            <CreateDatabase server={page.props.server.id} defaultCharset={defaultCharset} defaultCollation={defaultCollation}>
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">Create</span>
              </Button>
            </CreateDatabase>
          </div>
        </HeaderContainer>

        <DynamicTable
          tableData={page.props.databases}
          actions={(database) => (
            <div className="flex items-center justify-end">
              <DropdownMenu modal={false}>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" className="h-8 w-8 p-0">
                    <span className="sr-only">Open menu</span>
                    <MoreVerticalIcon />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <Delete database={database as unknown as Database} />
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          )}
        />
      </Container>
    </ServerLayout>
  );
}
