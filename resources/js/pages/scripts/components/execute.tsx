import React, { ReactNode, useState } from 'react';
import { useForm } from '@inertiajs/react';
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
import { LoaderCircleIcon } from 'lucide-react';
import FormSuccessful from '@/components/form-successful';
import { Script } from '@/types/script';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import ServerSelect from '@/pages/servers/components/server-select';
import { Server } from '@/types/server';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

export default function Execute({ script, children }: { script: Script; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const [server, setServer] = useState<Server>();
  const form = useForm<Record<string, string>>({
    server: '',
    user: '',
  });

  const submit = () => {
    form.post(`/scripts/${script.id}/execute`, {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Execute</DialogTitle>
          <DialogDescription className="sr-only">Execute script</DialogDescription>
        </DialogHeader>
        <div className="space-y-2 p-4">
          <p>Are you sure you want to run this script?</p>
          <Form id="execute-script-form" onSubmit={submit}>
            <FormFields>
              <FormField>
                <Label htmlFor="server">Server</Label>
                <ServerSelect
                  value={form.data.server}
                  onValueChange={(value) => {
                    form.setData('server', value ? value.id.toString() : '');
                    setServer(value);
                  }}
                />
                <InputError message={form.errors.server} />
              </FormField>
              <FormField>
                <Label htmlFor="user">User</Label>
                <Select value={form.data.user} onValueChange={(value) => form.setData('user', value)}>
                  <SelectTrigger id="user">
                    <SelectValue placeholder="Select a user" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      {server?.ssh_users.map((user) => (
                        <SelectItem key={`user-${user}`} value={user}>
                          {user}
                        </SelectItem>
                      ))}
                    </SelectGroup>
                  </SelectContent>
                </Select>
                <InputError message={form.errors.user} />
              </FormField>
              {script.variables.map((variable: string) => (
                <FormField key={`variable-${variable}`}>
                  <Label htmlFor={variable}>{variable}</Label>
                  <Input id={variable} name={variable} value={form.data[variable] || ''} onChange={(e) => form.setData(variable, e.target.value)} />
                  <InputError message={form.errors[variable as keyof typeof form.errors]} />
                </FormField>
              ))}
            </FormFields>
          </Form>
        </div>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Execute
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
