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
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';

function AddUser({ project }: { project: Project }) {
  const [open, setOpen] = useState(false);

  const form = useForm({
    user: 0,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('projects.users.store', { project: project.id }), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button>Add user</Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Add user</DialogTitle>
          <DialogDescription className="sr-only">Here you can add new user to {project.name}</DialogDescription>
        </DialogHeader>
        <Form id="add-user-form" onSubmit={submit} className="p-4">
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
          <Button form="add-user-form" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Add
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function RemoveUser({ project, user }: { project: Project; user: User }) {
  const [open, setOpen] = useState(false);

  const form = useForm();

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.delete(route('projects.users.destroy', { project: project.id, user: user.id }), {
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
          <DialogDescription className="sr-only">Remove user from {project.name}.</DialogDescription>
        </DialogHeader>

        <p className="p-4">
          Are you sure you want to remove <b>{user.name}</b> from this project?
        </p>

        <DialogFooter className="gap-2">
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
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
    <Sheet>
      <SheetTrigger asChild>{children}</SheetTrigger>
      <SheetContent className="sm:max-w-3xl">
        <SheetHeader>
          <SheetTitle>Project users</SheetTitle>
          <SheetDescription className="sr-only">Users assigned to this project</SheetDescription>
        </SheetHeader>
        <DataTable columns={getColumns(project)} data={project.users} modal />
        <SheetFooter className="gap-2">
          <div className="flex items-center">
            <AddUser project={project} />
          </div>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}
