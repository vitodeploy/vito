import { FormEvent, ReactNode, useState, useEffect } from 'react';
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
import { DatabaseUser } from '@/types/database-user';
import FormSuccessful from '@/components/form-successful';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

type EditForm = {
  password: string;
  remote: boolean;
  host?: string;
  permission: string;
};

export default function EditDatabaseUser({
  databaseUser,
  onDatabaseUserUpdated,
  children,
}: {
  databaseUser: DatabaseUser;
  onDatabaseUserUpdated?: () => void;
  children: ReactNode;
}) {
  const [open, setOpen] = useState(false);

  const form = useForm<EditForm>({
    password: '',
    remote: databaseUser.host !== 'localhost',
    host: databaseUser.host,
    permission: databaseUser.permission,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.put(route('database-users.update', { server: databaseUser.server_id, databaseUser: databaseUser.id }), {
      onSuccess: () => {
        form.reset();
        setOpen(false);
        if (onDatabaseUserUpdated) {
          onDatabaseUserUpdated();
        }
      },
    });
  };

  useEffect(() => {
    if (open) {
      form.setData({
        password: '',
        remote: databaseUser.host !== 'localhost',
        host: databaseUser.host,
        permission: databaseUser.permission,
      });
    }
  }, [open, databaseUser.host, databaseUser.permission]);

  const handleOpenChange = (open: boolean) => {
    setOpen(open);
    if (!open) {
      form.reset();
    }
  };

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Edit database user [{databaseUser.username}]</DialogTitle>
          <DialogDescription className="sr-only">Edit database user</DialogDescription>
        </DialogHeader>
        <Form className="p-4" id="edit-database-user-form" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="password">New Password (leave blank to keep current)</Label>
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
              <Label htmlFor="permission">Permission</Label>
              <Select value={form.data.permission} onValueChange={(value) => form.setData('permission', value)}>
                <SelectTrigger>
                  <SelectValue placeholder="Select permission" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="admin">Admin (Full Access)</SelectItem>
                  <SelectItem value="write">Write (No Drop/Truncate)</SelectItem>
                  <SelectItem value="read">Read Only</SelectItem>
                </SelectContent>
              </Select>
              <InputError message={form.errors.permission} />
            </FormField>
            <FormField>
              <div className="flex items-center space-x-3">
                <Checkbox id="remote" name="remote" checked={form.data.remote} onClick={() => form.setData('remote', !form.data.remote)} />
                <Label htmlFor="remote">Allow remote connection</Label>
              </div>
              <InputError message={form.errors.remote} />
            </FormField>
            {form.data.remote && (
              <FormField>
                <Label htmlFor="host">Allow connection from (% for all)</Label>
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
            <FormSuccessful successful={form.recentlySuccessful} />
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
