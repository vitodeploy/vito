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
import React, { FormEvent, ReactNode, useState } from 'react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { useForm, usePage } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Worker } from '@/types/worker';
import { SharedData } from '@/types';
import { Server } from '@/types/server';
import { Switch } from '@/components/ui/switch';
import { Site } from '@/types/site';

export default function WorkerForm({ serverId, site, worker, children }: { serverId: number; site?: Site; worker?: Worker; children: ReactNode }) {
  const page = usePage<SharedData & { server: Server; sites?: Array<{ id: number; domain: string }> }>();
  const [open, setOpen] = useState(false);
  const form = useForm<{
    name: string;
    command: string;
    user: string;
    auto_start: boolean;
    auto_restart: boolean;
    numprocs: string;
    site_id: string;
  }>({
    name: worker?.name || '',
    command: worker?.command || '',
    user: worker?.user || '',
    auto_start: worker?.auto_start || true,
    auto_restart: worker?.auto_restart || true,
    numprocs: worker?.numprocs.toString() || '',
    site_id: worker?.site_id?.toString() || '0',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (worker) {
      form.put(route('workers.update', { server: serverId, worker: worker.id }), {
        onSuccess: () => {
          setOpen(false);
          form.reset();
        },
      });
      return;
    }

    form.post(route('workers.store', { server: serverId, site: site?.id }), {
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
          <DialogTitle>{worker ? 'Edit' : 'Create'} worker</DialogTitle>
          <DialogDescription className="sr-only">{worker ? 'Edit' : 'Create new'} worker</DialogDescription>
        </DialogHeader>
        <Form id="worker-form" onSubmit={submit} className="p-4">
          <FormFields>
            {/*name*/}
            <FormField>
              <Label htmlFor="name">Name</Label>
              <Input type="text" id="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
              <InputError message={form.errors.name} />
            </FormField>

            {/*command*/}
            <FormField>
              <Label htmlFor="command">Command</Label>
              <Input
                type="text"
                id="command"
                value={form.data.command}
                onChange={(e) => form.setData('command', e.target.value)}
                placeholder={site ? 'php artisan queue:work' : ''}
              />
              <p className="text-muted-foreground text-sm">`php` command will use the default php cli command</p>
              <p className="text-muted-foreground text-sm">For specific version, use exact path to the php version like `/usr/bin/php8.4`</p>
              <InputError message={form.errors.command} />
            </FormField>

            {/*site selection - only show if we have sites data and not in site context*/}
            {page.props.sites && !site && (
              <FormField>
                <Label htmlFor="site_id">Site</Label>
                <Select value={form.data.site_id} onValueChange={(value) => form.setData('site_id', value)}>
                  <SelectTrigger id="site_id">
                    <SelectValue placeholder="Select a site (optional)" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      <SelectItem value="0">Server (no site)</SelectItem>
                      {page.props.sites.map((siteOption) => (
                        <SelectItem key={`site-${siteOption.id}`} value={siteOption.id.toString()}>
                          {siteOption.domain}
                        </SelectItem>
                      ))}
                    </SelectGroup>
                  </SelectContent>
                </Select>
                <InputError message={form.errors.site_id} />
              </FormField>
            )}

            {/*user*/}
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

            {/*numprocs*/}
            <FormField>
              <Label htmlFor="custom_frequency">Numprocs</Label>
              <Input
                id="numprocs"
                name="numprocs"
                value={form.data.numprocs}
                onChange={(e) => form.setData('numprocs', e.target.value)}
                placeholder="1"
              />
              <InputError message={form.errors.numprocs} />
            </FormField>

            <div className="grid grid-cols-2 gap-6">
              {/*auto start*/}
              <FormField>
                <div className="flex items-center space-x-2">
                  <Switch id="auto_start" checked={form.data.auto_start} onCheckedChange={(value) => form.setData('auto_start', value)} />
                  <Label htmlFor="auto_start">Auto start</Label>
                  <InputError message={form.errors.auto_start} />
                </div>
              </FormField>

              {/*auto restart*/}
              <FormField>
                <div className="flex items-center space-x-2">
                  <Switch id="auto_restart" checked={form.data.auto_restart} onCheckedChange={(value) => form.setData('auto_restart', value)} />
                  <Label htmlFor="auto_restart">Auto restart</Label>
                  <InputError message={form.errors.auto_restart} />
                </div>
              </FormField>
            </div>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Close</Button>
          </DialogClose>
          <Button form="worker-form" type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
