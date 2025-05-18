import { User } from '@/types/user';
import { FormEvent, ReactNode, useState } from 'react';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DialogTrigger } from '@radix-ui/react-dialog';
import { ColumnDef } from '@tanstack/react-table';
import type { Project } from '@/types/project';
import { DataTable } from '@/components/data-table';
import { useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon, TrashIcon } from 'lucide-react';
import { Sheet, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { SharedData } from '@/types';

function RemoveProject({ user, project }: { user: User; project: Project }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.delete(route('users.projects.destroy', { user: user.id, project: project.id }), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset();
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button key={`remove-user-${user.id}`} variant="outline" size="sm" tabIndex={-1}>
          <TrashIcon />
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Remove from project</DialogTitle>
          <DialogDescription className="sr-only">
            Remove <b>{user.name}</b> from <b>{project.name}</b>
          </DialogDescription>
        </DialogHeader>
        <p className="p-4">
          Are you sure you want to remove <b>{user.name}</b> from <b>{project.name}</b>?
        </p>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button variant="destructive" disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Remove
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function AddToProject({ user }: { user: User }) {
  const page = usePage<SharedData>();
  const [open, setOpen] = useState(false);
  const form = useForm({
    project: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('users.projects.store', { user: user.id }), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button key={`add-project-${user.id}`}>Add to project</Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Add to project</DialogTitle>
          <DialogDescription className="sr-only">
            Add <b>{user.name}</b> to a project
          </DialogDescription>
        </DialogHeader>
        <Form id="add-to-project-form" className="p-4" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="project">Project</Label>
              <Select value={form.data.project} onValueChange={(value) => form.setData('project', value)}>
                <SelectTrigger id="project">
                  <SelectValue placeholder="Select a project" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    {page.props.auth.projects.map((project: Project) => (
                      <SelectItem key={`project-${project.id}`} value={project.id.toString()}>
                        {project.name}
                      </SelectItem>
                    ))}
                  </SelectGroup>
                </SelectContent>
              </Select>
              <InputError message={form.errors.project} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button form="add-to-project-form" disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Add
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

const columns = (user: User): ColumnDef<Project>[] => {
  return [
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
      id: 'actions',
      enableColumnFilter: false,
      enableSorting: false,
      cell: ({ row }) => {
        return (
          <div className="flex items-center justify-end" key={row.id}>
            <RemoveProject user={user} project={row.original} />
          </div>
        );
      },
    },
  ];
};

export default function Projects({ user, children }: { user: User; children: ReactNode }) {
  return (
    <Sheet>
      <SheetTrigger asChild>{children}</SheetTrigger>
      <SheetContent className="sm:max-w-3xl">
        <SheetHeader>
          <SheetTitle>Projects</SheetTitle>
          <SheetDescription className="sr-only">
            All projects that <b>{user.name}</b> has access
          </SheetDescription>
        </SheetHeader>
        {user.projects && <DataTable columns={columns(user)} data={user.projects} modal />}
        <SheetFooter>
          <div className="flex items-center">
            <AddToProject user={user} />
          </div>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}
