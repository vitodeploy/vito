import { LoaderCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
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
import { useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, ReactNode, useState } from 'react';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Server } from '@/types/server';
import SshKeySelect from '@/pages/ssh-keys/components/ssh-key-select';

export default function DeployKey({ children }: { children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const page = usePage<{
    server: Server;
  }>();

  const form = useForm<
    Required<{
      key: string;
      user: string;
    }>
  >({
    key: '',
    user: page.props.server.ssh_user || page.props.server.ssh_users[0] || '',
  });

  const submit: FormEventHandler = (e) => {
    e.preventDefault();
    form.post(route('server-ssh-keys.store', { server: page.props.server.id }), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Deploy ssh key</DialogTitle>
          <DialogDescription className="sr-only">Deploy ssh key</DialogDescription>
        </DialogHeader>
        <Form id="deploy-ssh-key-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="key">Key</Label>
              <SshKeySelect value={form.data.key} onValueChange={(value) => form.setData('key', value)} />
              <InputError message={form.errors.key} />
            </FormField>
            <FormField>
              <Label htmlFor="user">User</Label>
              <Select value={form.data.user} onValueChange={(value) => form.setData('user', value)}>
                <SelectTrigger id="user">
                  <SelectValue placeholder="Select a user" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    {page.props.server.ssh_users.map((user) => (
                      <SelectItem key={`user-${user}`} value={user}>
                        {user}
                      </SelectItem>
                    ))}
                  </SelectGroup>
                </SelectContent>
              </Select>
              <InputError message={form.errors.user} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button type="button" variant="outline">
              Cancel
            </Button>
          </DialogClose>
          <Button form="deploy-ssh-key-form" onClick={submit} disabled={form.processing}>
            {form.processing && <LoaderCircle className="animate-spin" />}
            Deploy
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
