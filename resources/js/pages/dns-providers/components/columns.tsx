import { ColumnDef } from '@tanstack/react-table';
import DateTime from '@/components/date-time';
import { DNSProvider } from '@/types/dns-provider';
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
import { useForm, usePage } from '@inertiajs/react';
import { LoaderCircleIcon, MoreVerticalIcon } from 'lucide-react';
import FormSuccessful from '@/components/form-successful';
import { FormEvent, useState } from 'react';
import InputError from '@/components/ui/input-error';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { SharedData } from '@/types';
import { DynamicFieldConfig } from '@/types/dynamic-field-config';
import DynamicFieldComponent from '@/components/ui/dynamic-field';

function Edit({ dnsProvider }: { dnsProvider: DNSProvider }) {
  const [open, setOpen] = useState(false);
  const page = usePage<SharedData>();
  const providerConfig = page.props.configs.dns_provider.providers[dnsProvider.provider];
  const editFields: DynamicFieldConfig[] = providerConfig?.edit_form || [];

  const initialData: Record<string, string | number | boolean | string[]> = {
    name: dnsProvider.name,
    global: dnsProvider.project_id === null,
  };

  // Pre-fill editable (non-sensitive) data, leave credential fields empty
  editFields.forEach((field) => {
    initialData[field.name] = dnsProvider.editable_data?.[field.name] ?? '';
  });

  const form = useForm(initialData);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.patch(route('dns-providers.update', dnsProvider.id), {
      onSuccess: () => setOpen(false),
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Edit</DropdownMenuItem>
      </DialogTrigger>
      <DialogContent className="max-h-screen overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Edit {dnsProvider.name}</DialogTitle>
          <DialogDescription className="sr-only">Edit DNS provider</DialogDescription>
        </DialogHeader>
        <Form id="edit-dns-provider-form" className="p-4" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="name">Name</Label>
              <Input type="text" id="name" name="name" value={form.data.name as string} onChange={(e) => form.setData('name', e.target.value)} />
              <InputError message={form.errors.name} />
            </FormField>
            {editFields.map((field: DynamicFieldConfig) => (
              <DynamicFieldComponent
                key={`edit-field-${field.name}`}
                value={form.data[field.name]}
                onChange={(value) => form.setData(field.name, value)}
                config={field}
                error={form.errors[field.name]}
              />
            ))}
            <FormField>
              <div className="flex items-center space-x-3">
                <Checkbox id="global" name="global" checked={form.data.global as boolean} onClick={() => form.setData('global', !form.data.global)} />
                <Label htmlFor="global">Is global (accessible in all projects)</Label>
              </div>
              <InputError message={form.errors.global} />
            </FormField>
            <InputError message={form.errors.provider} />
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button form="edit-dns-provider-form" disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function Delete({ dnsProvider }: { dnsProvider: DNSProvider }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.delete(route('dns-providers.destroy', dnsProvider.id), {
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
          <DialogTitle>Delete {dnsProvider.name}</DialogTitle>
          <DialogDescription className="sr-only">Delete DNS provider</DialogDescription>
        </DialogHeader>
        <div className="space-y-2 p-4">
          <p>
            Are you sure you want to delete <strong>{dnsProvider.name}</strong>?
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

export const columns: ColumnDef<DNSProvider>[] = [
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
    header: 'Scope',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return <div>{row.original.global ? <Badge variant="outline">global</Badge> : <Badge variant="outline">project</Badge>}</div>;
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
              <Edit dnsProvider={row.original} />
              <DropdownMenuSeparator />
              <Delete dnsProvider={row.original} />
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];
