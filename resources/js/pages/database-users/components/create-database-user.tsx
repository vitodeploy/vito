import { Server } from '@/types/server';
import React, { FormEvent, ReactNode, useState } from 'react';
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
import { Form, FormField, FormFields } from '@/components/ui/form';
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { LoaderCircle } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Checkbox } from '@/components/ui/checkbox';

type CreateForm = {
  username: string;
  password: string;
  remote: boolean;
  host: string;
};

export default function CreateDatabaseUser({ server, children }: { server: Server; children: ReactNode }) {
  const [open, setOpen] = useState(false);

  const form = useForm<CreateForm>({
    username: '',
    password: '',
    remote: false,
    host: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('database-users.store', server.id), {
      onSuccess: () => {
        form.reset();
        setOpen(false);
      },
    });
  };

  const handleOpenChange = (open: boolean) => {
    setOpen(open);
  };

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Create database user</DialogTitle>
          <DialogDescription className="sr-only">Create new database user</DialogDescription>
        </DialogHeader>
        <Form className="p-4" id="create-database-user-form" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="username">Username</Label>
              <Input
                type="text"
                id="username"
                name="username"
                value={form.data.username}
                onChange={(e) => form.setData('username', e.target.value)}
              />
              <InputError message={form.errors.username} />
            </FormField>
            <FormField>
              <Label htmlFor="password">Password</Label>
              <Input
                type="password"
                id="password"
                name="password"
                value={form.data.password}
                onChange={(e) => form.setData('password', e.target.value)}
              />
              <InputError message={form.errors.password} />
            </FormField>
            <FormField>
              <div className="flex items-center space-x-3">
                <Checkbox id="remote" name="remote" checked={form.data.remote} onClick={() => form.setData('remote', !form.data.remote)} />
                <Label htmlFor="remote">Allow remote access</Label>
              </div>
              <InputError message={form.errors.remote} />
            </FormField>
            {form.data.remote && (
              <FormField>
                <Label htmlFor="host">Host</Label>
                <Input type="text" id="host" name="host" value={form.data.host} onChange={(e) => form.setData('host', e.target.value)} />
                <InputError message={form.errors.host} />
              </FormField>
            )}
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button type="button" variant="outline">
              Cancel
            </Button>
          </DialogClose>
          <Button type="button" onClick={submit} disabled={form.processing}>
            {form.processing && <LoaderCircle className="animate-spin" />}
            Create
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
