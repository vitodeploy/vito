import React, { FormEvent, ReactNode, useState } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import { SharedData } from '@/types';
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
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon } from 'lucide-react';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

export default function InstallService({ name, children }: { name?: string; children: ReactNode }) {
  const page = usePage<
    {
      server: Server;
    } & SharedData
  >();

  const [open, setOpen] = useState(false);
  const form = useForm<{
    type: string;
    name: string;
    version: string;
  }>({
    type: '',
    name: name ?? '',
    version: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('services.store', { server: page.props.server.id }), {
      onSuccess: () => {
        setOpen(false);
        form.reset();
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Install {name ?? 'service'}</DialogTitle>
          <DialogDescription className="sr-only">Install new {name ?? 'service'}</DialogDescription>
        </DialogHeader>
        <Form id="install-service-form" onSubmit={submit} className="p-4">
          <FormFields>
            {/*service*/}
            {!name && (
              <FormField>
                <Label htmlFor="name">Name</Label>
                <Select
                  value={form.data.name}
                  onValueChange={(value) => {
                    form.setData('name', value);
                    form.setData('version', '');
                  }}
                >
                  <SelectTrigger id="name">
                    <SelectValue placeholder="Select a service" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      {Object.entries(page.props.configs.service.services).map(([key, service]) => (
                        <SelectItem key={`service-${key}`} value={key}>
                          {service.label}
                        </SelectItem>
                      ))}
                    </SelectGroup>
                  </SelectContent>
                </Select>
                <InputError message={form.errors.type || form.errors.name} />
              </FormField>
            )}

            {/*version*/}
            <FormField>
              <Label htmlFor="version">Version</Label>
              <Select value={form.data.version} onValueChange={(value) => form.setData('version', value)}>
                <SelectTrigger id="version">
                  <SelectValue placeholder="Select a version" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    {form.data.name &&
                      page.props.configs.service.services[form.data.name].versions.map((version) => (
                        <SelectItem key={`version-${form.data.name}-${version}`} value={version}>
                          {version}
                        </SelectItem>
                      ))}
                  </SelectGroup>
                </SelectContent>
              </Select>
              <InputError message={form.errors.version} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Close</Button>
          </DialogClose>
          <Button form="install-service-form" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Install
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
