import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import ServerLayout from '@/layouts/server/layout';
import { DynamicTable } from '@/components/dynamic-table';
import { DynamicTableData } from '@/types/dynamic-table';
import { BookOpenIcon, LoaderCircleIcon, MoreVerticalIcon, PlusIcon } from 'lucide-react';
import CreateDatabaseUser from '@/pages/database-users/components/create-database-user';
import SyncUsers from '@/pages/database-users/components/sync-users';
import { DatabaseUser } from '@/types/database-user';
import { Database } from '@/types/database';
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
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { MultiSelect } from '@/components/multi-select';
import EditDatabaseUser from '@/pages/database-users/components/edit-database-user';

function Link({ databaseUser }: { databaseUser: DatabaseUser }) {
  const [open, setOpen] = useState(false);
  const page = usePage<{
    databases: Database[];
  }>();
  const form = useForm<{
    databases: string[];
  }>({
    databases: databaseUser.databases,
  });

  const databases = page.props.databases.map((database) => ({
    value: database.name,
    label: database.name,
  }));

  const submit = () => {
    form.put(route('database-users.link', { server: databaseUser.server_id, databaseUser: databaseUser.id }), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Link</DropdownMenuItem>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Link database user [{databaseUser.username}]</DialogTitle>
          <DialogDescription className="sr-only">Link database user</DialogDescription>
        </DialogHeader>
        <Form id="link-database-user" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="databases">Databases</Label>
              <MultiSelect
                options={databases}
                onValueChange={(value) => form.setData('databases', value)}
                defaultValue={form.data.databases}
                placeholder="Select database"
                maxCount={5}
              />
              <InputError className="mt-2" message={form.errors.databases} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function Delete({ databaseUser }: { databaseUser: DatabaseUser }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.delete(route('database-users.destroy', { server: databaseUser.server_id, databaseUser: databaseUser.id }), {
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
          <DialogTitle>Delete database user [{databaseUser.username}]</DialogTitle>
          <DialogDescription className="sr-only">Delete database user</DialogDescription>
        </DialogHeader>
        <p className="p-4">
          Are you sure you want to delete database user <strong>{databaseUser.username}</strong>? This action cannot be undone.
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
  databaseUsers: DynamicTableData;
};

export default function Databases() {
  const page = usePage<Page>();

  return (
    <ServerLayout>
      <Head title={`Users - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Users" description="Here you can manage the database users and their permissions" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/database" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <SyncUsers server={page.props.server} />
            <CreateDatabaseUser server={page.props.server.id}>
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">Create</span>
              </Button>
            </CreateDatabaseUser>
          </div>
        </HeaderContainer>

        <DynamicTable
          tableData={page.props.databaseUsers}
          actions={(databaseUser) => (
            <div className="flex items-center justify-end">
              <DropdownMenu modal={false}>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" className="h-8 w-8 p-0">
                    <span className="sr-only">Open menu</span>
                    <MoreVerticalIcon />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <EditDatabaseUser databaseUser={databaseUser as unknown as DatabaseUser}>
                    <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Edit</DropdownMenuItem>
                  </EditDatabaseUser>
                  <Link databaseUser={databaseUser as unknown as DatabaseUser} />
                  <DropdownMenuSeparator />
                  <Delete databaseUser={databaseUser as unknown as DatabaseUser} />
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          )}
        />
      </Container>
    </ServerLayout>
  );
}
