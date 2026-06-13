import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import FormSuccessful from '@/components/form-successful';
import { FormEvent } from 'react';
import { DatabaseUser } from '@/types/database-user';
import InputError from '@/components/ui/input-error';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

export default function EditDatabaseUser({
  open,
  onOpenChange,
  databaseUser,
  usesHost = true,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  databaseUser: DatabaseUser;
  usesHost?: boolean;
}) {
  const form = useForm<{
    password: string;
    remote: boolean;
    host?: string;
    permission: string;
  }>({
    password: '',
    remote: databaseUser.host !== 'localhost',
    host: databaseUser.host,
    permission: databaseUser.permission,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.put(route('database-users.update', { server: databaseUser.server_id, databaseUser: databaseUser.id }), {
      onSuccess: () => onOpenChange(false),
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Edit database user [{databaseUser.username}]</DialogTitle>
          <DialogDescription className="sr-only">Edit database user</DialogDescription>
        </DialogHeader>
        <Form id="edit-database-user-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="password">New Password (leave blank to keep current)</Label>
              <Input id="password" type="password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} />
              <InputError message={form.errors.password} />
            </FormField>

            <FormField>
              <Label htmlFor="permission">Permission</Label>
              <Select value={form.data.permission} onValueChange={(value) => form.setData('permission', value)}>
                <SelectTrigger className="w-full">
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

            {usesHost && (
              <>
                <FormField>
                  <div className="flex items-center space-x-3">
                    <Checkbox id="remote" checked={form.data.remote} onClick={() => form.setData('remote', !form.data.remote)} />
                    <Label htmlFor="remote">Allow remote connection</Label>
                  </div>
                  <InputError message={form.errors.remote} />
                </FormField>

                {form.data.remote && (
                  <FormField>
                    <Label htmlFor="host">Allow connection from (% for all)</Label>
                    <Input id="host" type="text" value={form.data.host} onChange={(e) => form.setData('host', e.target.value)} />
                    <InputError message={form.errors.host} />
                  </FormField>
                )}
              </>
            )}
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button form="edit-database-user-form" type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
