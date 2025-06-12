import { ColumnDef } from '@tanstack/react-table';
import DateTime from '@/components/date-time';
import { SourceControl } from '@/types/source-control';
import { Badge } from '@/components/ui/badge';
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
import { FormEvent, useState } from 'react';
import InputError from '@/components/ui/input-error';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';

function Edit({ sourceControl }: { sourceControl: SourceControl }) {
  const [open, setOpen] = useState(false);
  const form = useForm({
    name: sourceControl.name,
    global: sourceControl.global,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.patch(route('source-controls.update', sourceControl.id), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };
  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Edit</DropdownMenuItem>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Edit {sourceControl.name}</DialogTitle>
          <DialogDescription className="sr-only">Edit source control</DialogDescription>
        </DialogHeader>
        <Form id="edit-source-control-form" className="p-4" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="name">Name</Label>
              <Input type="text" id="name" name="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
              <InputError message={form.errors.name} />
            </FormField>
            <FormField>
              <div className="flex items-center space-x-3">
                <Checkbox id="global" name="global" checked={form.data.global} onClick={() => form.setData('global', !form.data.global)} />
                <Label htmlFor="global">Is global (accessible in all projects)</Label>
              </div>
              <InputError message={form.errors.global} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button form="edit-source-control-form" disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function Delete({ sourceControl }: { sourceControl: SourceControl }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.delete(route('source-controls.destroy', sourceControl.id), {
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
          <DialogTitle>Delete {sourceControl.name}</DialogTitle>
          <DialogDescription className="sr-only">Delete source control</DialogDescription>
        </DialogHeader>
        <div className="space-y-2 p-4">
          <p>
            Are you sure you want to delete <strong>{sourceControl.name}</strong>?
          </p>
          <InputError message={form.errors.provider} />
        </div>
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

export const columns: ColumnDef<SourceControl>[] = [
  {
    accessorKey: 'id',
    header: 'ID',
    enableColumnFilter: true,
    enableSorting: true,
    enableHiding: true,
  },
  {
    accessorKey: 'provider',
    header: 'Provider',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    accessorKey: 'name',
    header: 'Name',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    accessorKey: 'global',
    header: 'Global',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return <div>{row.original.global ? <Badge variant="success">yes</Badge> : <Badge variant="danger">no</Badge>}</div>;
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
              <Edit sourceControl={row.original} />
              <DropdownMenuSeparator />
              <Delete sourceControl={row.original} />
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];
