import { Service } from '@/types/service';
import React, { FormEvent, useState } from 'react';
import { useForm, usePage } from '@inertiajs/react';
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
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { SharedData } from '@/types';
import InputError from '@/components/ui/input-error';

export default function Extensions({ service }: { service: Service }) {
  const page = usePage<SharedData>();
  const [open, setOpen] = useState(false);
  const form = useForm<{
    extension: string;
    version: string;
  }>({
    extension: '',
    version: service.version,
  });
  const [, php] = Object.entries(page.props.configs.service.services).filter(([key]) => key === 'php')[0] || null;

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('php.install-extension', { server: service.server_id, service: service.id }), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };

  if (!php) {
    return null;
  }

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Extensions</DropdownMenuItem>
      </DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Install extension</DialogTitle>
          <DialogDescription className="sr-only">Install php extension</DialogDescription>
        </DialogHeader>
        <Form id="install-extension-form" className="p-4" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="extension">Extension</Label>
              <Select value={form.data.extension} onValueChange={(value) => form.setData('extension', value)}>
                <SelectTrigger id="extension">
                  <SelectValue placeholder="Select an extension" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    {php.data?.extensions?.map((extension: string) => (
                      <SelectItem key={`extension-${extension}`} value={extension} disabled={service.type_data.extensions?.includes(extension)}>
                        {extension} {service.type_data.extensions?.includes(extension) && <span>(installed)</span>}
                      </SelectItem>
                    ))}
                  </SelectGroup>
                </SelectContent>
              </Select>
              <InputError message={form.errors.extension} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button form="install-extension-form" disabled={form.processing} onClick={submit} className="ml-2">
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Install
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
