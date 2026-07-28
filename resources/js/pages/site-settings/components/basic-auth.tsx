import { FormEvent, ReactNode, useState } from 'react';
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
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { PasswordInput } from '@/components/ui/password-input';
import InputError from '@/components/ui/input-error';
import { Switch } from '@/components/ui/switch';
import { LoaderCircleIcon, PlusIcon, TrashIcon } from 'lucide-react';
import { Site } from '@/types/site';
import { rowId } from '@/lib/utils';

type FormUser = { id: string; username: string; password: string; existing: boolean };

export default function BasicAuth({ site, children }: { site: Site; children: ReactNode }) {
  const [open, setOpen] = useState(false);

  const initialUsers = (): FormUser[] =>
    (site.basic_auth?.users ?? []).map((u) => ({ id: rowId(), username: u.username, password: '', existing: true }));

  const form = useForm<{
    enabled: boolean;
    users: FormUser[];
  }>({
    enabled: !!site.basic_auth?.enabled,
    users: initialUsers(),
  });

  const handleOpenChange = (next: boolean) => {
    if (next) {
      form.setData({
        enabled: !!site.basic_auth?.enabled,
        users: initialUsers(),
      });
      form.clearErrors();
    }
    setOpen(next);
  };

  const setUser = (index: number, patch: Partial<FormUser>) => {
    const next = form.data.users.map((u, i) => (i === index ? { ...u, ...patch } : u));
    form.setData('users', next);
  };

  const addUser = () => {
    form.setData('users', [...form.data.users, { id: rowId(), username: '', password: '', existing: false }]);
  };

  const removeUser = (index: number) => {
    const next = form.data.users.filter((_, i) => i !== index);
    form.setData('users', next.length === 0 ? [] : next);
    if (next.length === 0) {
      form.setData('enabled', false);
    }
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.transform((data) => ({
      enabled: data.enabled,
      users: data.users.map(({ username, password }) => ({ username, password })),
    }));
    form.patch(route('site-settings.update-basic-auth', { server: site.server_id, site: site.id }), {
      preserveScroll: true,
      onSuccess: () => setOpen(false),
    });
  };

  const hasUsers = form.data.users.length > 0;

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Basic Auth</DialogTitle>
          <DialogDescription>Protect this site with HTTP Basic Authentication.</DialogDescription>
        </DialogHeader>

        <Form id="basic-auth-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <div className="flex items-center justify-between gap-4">
                <div className="space-y-1">
                  <Label htmlFor="basic-auth-enabled">Enable Basic Auth</Label>
                  <p className="text-muted-foreground text-sm">
                    {hasUsers ? 'Require credentials to access the site.' : 'Add at least one user to enable.'}
                  </p>
                </div>
                <Switch
                  id="basic-auth-enabled"
                  checked={form.data.enabled && hasUsers}
                  disabled={!hasUsers}
                  onCheckedChange={(v) => form.setData('enabled', v)}
                />
              </div>
              <InputError message={form.errors.enabled} />
            </FormField>

            <FormField>
              <Label>Users</Label>
              <div className="space-y-2">
                {form.data.users.length === 0 && <p className="text-muted-foreground text-sm">No users yet. Add a user to start using basic auth.</p>}
                {form.data.users.map((u, i) => (
                  <div key={u.id} className="flex items-start gap-2">
                    <div className="flex-1">
                      <Input placeholder="username" value={u.username} onChange={(e) => setUser(i, { username: e.target.value })} />
                      <InputError message={(form.errors as Record<string, string>)[`users.${i}.username`]} />
                    </div>
                    <div className="flex-1">
                      <PasswordInput
                        placeholder={u.existing ? 'Leave blank to keep current' : 'password'}
                        value={u.password}
                        onChange={(e) => setUser(i, { password: e.target.value })}
                      />
                      <InputError message={(form.errors as Record<string, string>)[`users.${i}.password`]} />
                    </div>
                    <Button type="button" variant="outline" size="icon" onClick={() => removeUser(i)}>
                      <TrashIcon className="size-4" />
                    </Button>
                  </div>
                ))}
                <Button type="button" variant="outline" size="sm" onClick={addUser}>
                  <PlusIcon className="size-4" />
                  Add user
                </Button>
              </div>
              <InputError message={form.errors.users} />
            </FormField>
          </FormFields>
        </Form>

        <DialogFooter className="gap-2">
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button form="basic-auth-form" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="size-4 animate-spin" />}
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
