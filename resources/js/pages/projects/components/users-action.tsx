import { Project } from '@/types/project';
import { ColumnDef } from '@tanstack/react-table';
import { User } from '@/types/user';
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
import { FormEvent, ReactNode, useState } from 'react';
import { DataTable } from '@/components/data-table';
import { Button } from '@/components/ui/button';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import UserSelect from '@/components/user-select';
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon, TrashIcon } from 'lucide-react';

function AddUser({ project }: { project: Project }) {
  const [open, setOpen] = useState(false);

  const form = useForm({
    user: 0,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('projects.users', project.id), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="outline">Add user</Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Add user</DialogTitle>
          <DialogDescription>Here you can add new user to {project.name}</DialogDescription>
        </DialogHeader>
        <Form id="add-user-form" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="user">User</Label>
              <UserSelect onChange={(user: User) => form.setData('user', user.id)} />
              <InputError message={form.errors.user} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter className="gap-2">
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button form="add-user-form">Add</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function RemoveUser({ project, user }: { project: Project; user: User }) {
  const [open, setOpen] = useState(false);

  const form = useForm({
    user: user.id,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.delete(route('projects.users', project.id), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="outline" size="sm">
          <TrashIcon />
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Remove user</DialogTitle>
          <DialogDescription>Remove user from {project.name}.</DialogDescription>
        </DialogHeader>

        <DialogFooter className="gap-2">
          <DialogClose asChild>
            <Button variant="secondary">Cancel</Button>
          </DialogClose>

          <Button onClick={submit} variant="destructive" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Remove user
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

const getColumns = (project: Project): ColumnDef<User>[] => [
  {
    accessorKey: 'id',
    header: 'ID',
    enableColumnFilter: true,
    enableSorting: true,
    enableHiding: true,
  },
  {
    accessorKey: 'name',
    header: 'Name',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    accessorKey: 'email',
    header: 'Email',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    id: 'actions',
    enableColumnFilter: false,
    enableSorting: false,
    cell: ({ row }) => {
      return (
        <div className="flex items-center justify-end">
          <RemoveUser user={row.original} project={project} />
        </div>
      );
    },
  },
];

export default function UsersAction({ project, children }: { project: Project; children: ReactNode }) {
  return (
    <Dialog>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="sm:max-w-3xl">
        <DialogHeader>
          <DialogTitle>Users</DialogTitle>
          <DialogDescription>Users assigned to this project</DialogDescription>
        </DialogHeader>
        <DataTable columns={getColumns(project)} data={project.users} />
        <DialogFooter className="gap-2">
          <AddUser project={project} />
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
