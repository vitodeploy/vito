import React, { FormEvent, ReactNode, useState } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
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
import { CheckIcon, ChevronsUpDownIcon, LoaderCircleIcon } from 'lucide-react';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Command, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { cn } from '@/lib/utils';
import { useConfigs } from '@/stores/bootstrap-store';

export default function InstallService({ name, children }: { name?: string; children: ReactNode }) {
  const page = usePage<{ server: Server }>();
  const configs = useConfigs()!;

  const [open, setOpen] = useState(false);
  const [nameOpen, setNameOpen] = useState(false);
  const [versionOpen, setVersionOpen] = useState(false);
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
                <Popover open={nameOpen} onOpenChange={setNameOpen}>
                  <PopoverTrigger asChild>
                    <Button id="name" variant="outline" role="combobox" aria-expanded={nameOpen} className="w-full justify-between font-normal">
                      {form.data.name ? configs.service.services[form.data.name]?.label || form.data.name : 'Select a service'}
                      <ChevronsUpDownIcon className="ml-2 size-4 shrink-0 opacity-50" />
                    </Button>
                  </PopoverTrigger>
                  <PopoverContent className="w-[--radix-popover-trigger-width] p-0" align="start">
                    <Command>
                      <CommandInput placeholder="Search service..." />
                      <CommandList>
                        <CommandGroup>
                          {Object.entries(configs.service.services).map(([key, service]) => (
                            <CommandItem
                              key={`service-${key}`}
                              value={service.label}
                              onSelect={() => {
                                form.setData('name', key);
                                form.setData('version', '');
                                setNameOpen(false);
                              }}
                            >
                              {service.label}
                              <CheckIcon className={cn('ml-auto', form.data.name === key ? 'opacity-100' : 'opacity-0')} />
                            </CommandItem>
                          ))}
                        </CommandGroup>
                      </CommandList>
                    </Command>
                  </PopoverContent>
                </Popover>
                <InputError message={form.errors.type || form.errors.name} />
              </FormField>
            )}

            {/*version*/}
            <FormField>
              <Label htmlFor="version">Version</Label>
              <Popover open={versionOpen} onOpenChange={setVersionOpen}>
                <PopoverTrigger asChild>
                  <Button
                    id="version"
                    variant="outline"
                    role="combobox"
                    aria-expanded={versionOpen}
                    className="w-full justify-between font-normal"
                    disabled={!form.data.name}
                  >
                    {form.data.version || 'Select a version'}
                    <ChevronsUpDownIcon className="ml-2 size-4 shrink-0 opacity-50" />
                  </Button>
                </PopoverTrigger>
                <PopoverContent className="w-[--radix-popover-trigger-width] p-0" align="start">
                  <Command>
                    <CommandInput placeholder="Search version..." />
                    <CommandList>
                      <CommandGroup>
                        {form.data.name &&
                          configs.service.services[form.data.name].versions.map((version) => (
                            <CommandItem
                              key={`version-${form.data.name}-${version}`}
                              value={version}
                              onSelect={() => {
                                form.setData('version', version);
                                setVersionOpen(false);
                              }}
                            >
                              {version}
                              <CheckIcon className={cn('ml-auto', form.data.version === version ? 'opacity-100' : 'opacity-0')} />
                            </CommandItem>
                          ))}
                      </CommandGroup>
                    </CommandList>
                  </Command>
                </PopoverContent>
              </Popover>
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
