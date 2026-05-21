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
import { FormEvent, useMemo, useState } from 'react';
import InputError from '@/components/ui/input-error';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { DynamicFieldConfig } from '@/types/dynamic-field-config';
import DynamicField from '@/components/ui/dynamic-field';
import { useConfigs } from '@/stores/bootstrap-store';

function EditForm({ sourceControl, onSuccess }: { sourceControl: SourceControl; onSuccess: () => void }) {
  const isGithubApp = sourceControl.provider === 'github-app';
  const configs = useConfigs()!;
  const providerConfig = configs.source_control?.providers?.[sourceControl.provider];

  const editableFormFields = useMemo<DynamicFieldConfig[]>(() => {
    const editableFields = providerConfig?.editable_fields ?? [];
    return (providerConfig?.form ?? []).filter((f) => editableFields.includes(f.name));
  }, [providerConfig]);

  const form = useForm<Record<string, unknown>>({
    name: sourceControl.name,
    global: sourceControl.global,
    ...Object.fromEntries(editableFormFields.map((f) => [f.name, sourceControl[f.name] ?? f.default ?? null])),
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.patch(route('source-controls.update', sourceControl.id), {
      onSuccess,
    });
  };

  return (
    <>
      <Form id="edit-source-control-form" className="p-4" onSubmit={submit}>
        <FormFields>
          <FormField>
            <Label htmlFor="name">Name</Label>
            <Input
              type="text"
              id="name"
              name="name"
              value={form.data.name as string}
              onChange={(e) => form.setData('name', e.target.value)}
              disabled={isGithubApp}
              readOnly={isGithubApp}
            />
            {isGithubApp && <p className="text-muted-foreground text-xs">The name is the GitHub organization and cannot be changed.</p>}
            <InputError message={form.errors.name as string | undefined} />
          </FormField>
          {editableFormFields.map((field) => (
            <DynamicField
              key={field.name}
              value={form.data[field.name] as string | number | boolean | string[] | undefined}
              onChange={(value) => form.setData(field.name, value)}
              config={field}
              error={form.errors[field.name] as string | undefined}
            />
          ))}
          <FormField>
            <div className="flex items-center space-x-3">
              <Checkbox
                id="global"
                name="global"
                checked={form.data.global as boolean}
                onCheckedChange={(checked) => form.setData('global', checked === true)}
              />
              <Label htmlFor="global">Is global (accessible in all projects)</Label>
            </div>
            <InputError message={form.errors.global as string | undefined} />
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
    </>
  );
}

function Edit({ sourceControl }: { sourceControl: SourceControl }) {
  const [open, setOpen] = useState(false);
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
        {open && <EditForm sourceControl={sourceControl} onSuccess={() => setOpen(false)} />}
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
          <InputError message={form.errors.source_control} />
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
      const isGithubApp = row.original.provider === 'github-app';
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
              {isGithubApp && row.original.github_app?.html_url && (
                <>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem asChild>
                    <a href={row.original.github_app.html_url} target="_blank" rel="noopener noreferrer">
                      Manage permissions
                    </a>
                  </DropdownMenuItem>
                </>
              )}
              {!isGithubApp && (
                <>
                  <DropdownMenuSeparator />
                  <Delete sourceControl={row.original} />
                </>
              )}
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];
