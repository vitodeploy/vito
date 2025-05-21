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
import { useForm, usePage } from '@inertiajs/react';
import { LoaderCircleIcon, MoreVerticalIcon } from 'lucide-react';
import FormSuccessful from '@/components/form-successful';
import { useState } from 'react';
import { DatabaseUser } from '@/types/database-user';
import { Database } from '@/types/database';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { MultiSelect } from '@/components/multi-select';
import { Badge } from '@/components/ui/badge';

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
                variant="default"
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

export const columns: ColumnDef<DatabaseUser>[] = [
  {
    accessorKey: 'username',
    header: 'Username',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    accessorKey: 'databases',
    header: 'Linked databases',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return (
        <div className="flex items-center">
          {row.original.databases.map((database) => (
            <Badge key={database} variant="outline" className="mr-1">
              {database}
            </Badge>
          ))}
        </div>
      );
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
              <Link databaseUser={row.original} />
              <DropdownMenuSeparator />
              <Delete databaseUser={row.original} />
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];
