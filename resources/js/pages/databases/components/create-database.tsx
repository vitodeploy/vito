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
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import axios from 'axios';
import { Checkbox } from '@/components/ui/checkbox';

type CreateForm = {
  name: string;
  charset: string;
  collation: string;
  user: boolean;
  username: string;
  password: string;
  remote: boolean;
  host: string;
};

export default function CreateDatabase({ server, children }: { server: Server; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const [charsets, setCharsets] = useState<string[]>([]);
  const [collations, setCollations] = useState<string[]>([]);

  const fetchCharsets = async () => {
    axios.get(route('databases.charsets', server.id)).then((response) => {
      setCharsets(response.data);
    });
  };

  const form = useForm<CreateForm>({
    name: '',
    charset: '',
    collation: '',
    user: false,
    username: '',
    password: '',
    remote: false,
    host: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('databases.store', server.id), {
      onSuccess: () => {
        form.reset();
        setOpen(false);
      },
    });
  };

  const handleOpenChange = (open: boolean) => {
    setOpen(open);
    if (open && charsets.length === 0) {
      fetchCharsets();
    }
  };

  const handleCharsetChange = (value: string) => {
    form.setData('collation', '');
    form.setData('charset', value);
    axios.get(route('databases.collations', { server: server.id, charset: value })).then((response) => {
      setCollations(response.data);
    });
  };

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Create database</DialogTitle>
          <DialogDescription className="sr-only">Create new database</DialogDescription>
        </DialogHeader>
        <Form className="p-4" id="create-database-form" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="name">Name</Label>
              <Input type="text" id="name" name="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
              <InputError message={form.errors.name} />
            </FormField>
            <FormField>
              <Label htmlFor="charset">Charset</Label>
              <Select onValueChange={handleCharsetChange} defaultValue={form.data.charset}>
                <SelectTrigger id="charset">
                  <SelectValue placeholder="Select charset" />
                </SelectTrigger>
                <SelectContent>
                  {charsets.map((charset) => (
                    <SelectItem key={charset} value={charset}>
                      {charset}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <InputError message={form.errors.charset} />
            </FormField>
            <FormField>
              <Label htmlFor="collation">Collation</Label>
              <Select onValueChange={(value) => form.setData('collation', value)} defaultValue={form.data.collation}>
                <SelectTrigger id="collation">
                  <SelectValue placeholder="Select collation" />
                </SelectTrigger>
                <SelectContent>
                  {collations.map((collation) => (
                    <SelectItem key={collation} value={collation}>
                      {collation}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <InputError message={form.errors.collation} />
            </FormField>
            <FormField>
              <div className="flex items-center space-x-3">
                <Checkbox id="user" name="user" checked={form.data.user} onClick={() => form.setData('user', !form.data.user)} />
                <Label htmlFor="user">Create user</Label>
              </div>
              <InputError message={form.errors.user} />
            </FormField>
            {form.data.user && (
              <>
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
              </>
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
