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
import { Site } from '@/types/site';

type CreateForm = {
  mode: string;
  from: string;
  to: string;
};

export default function CreateRedirect({ site, children }: { site: Site; children: ReactNode }) {
  const [open, setOpen] = useState(false);

  const form = useForm<CreateForm>({
    mode: '',
    from: '',
    to: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('redirects.store', { server: site.server_id, site: site.id }), {
      onSuccess: () => {
        form.reset();
        setOpen(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Create Redirect</DialogTitle>
          <DialogDescription className="sr-only">Create new Redirect</DialogDescription>
        </DialogHeader>
        <Form className="p-4" id="create-redirect-form" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="mode">Mode</Label>
              <Select onValueChange={(value) => form.setData('mode', value)} defaultValue={form.data.mode}>
                <SelectTrigger id="mode">
                  <SelectValue placeholder="Select mode" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="301">301 - Moved Permanently</SelectItem>
                  <SelectItem value="302">302 - Found</SelectItem>
                  <SelectItem value="307">307 - Temporary Redirect</SelectItem>
                  <SelectItem value="308">308 - Permanent Redirect</SelectItem>
                  <SelectItem value="1000">Proxy (/docs to https://docs.example.com)</SelectItem>
                </SelectContent>
              </Select>
              <InputError message={form.errors.mode} />
            </FormField>
            <FormField>
              <Label htmlFor="from">From</Label>
              <Input
                type="text"
                id="from"
                name="from"
                value={form.data.from}
                onChange={(e) => form.setData('from', e.target.value)}
                placeholder="/path/to/redirect/"
              />
              <InputError message={form.errors.from} />
            </FormField>
            <FormField>
              <Label htmlFor="to">To</Label>
              <Input
                type="text"
                id="to"
                name="to"
                value={form.data.to}
                onChange={(e) => form.setData('to', e.target.value)}
                placeholder="https://new-url/"
              />
              <InputError message={form.errors.to} />
            </FormField>
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
