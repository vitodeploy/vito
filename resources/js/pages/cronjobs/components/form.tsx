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
import { CronJob } from '@/types/cronjob';
import { SharedData } from '@/types';
import { Server } from '@/types/server';
import { Site } from '@/types/site';

export default function CronJobForm({
  serverId,
  site,
  cronJob,
  children,
}: {
  serverId: number;
  site?: Site;
  cronJob?: CronJob;
  children: ReactNode;
}) {
  const page = usePage<SharedData & { server: Server; sites?: Array<{ id: number; domain: string }> }>();
  const [open, setOpen] = useState(false);
  const form = useForm<{
    command: string;
    user: string;
    frequency: string;
    custom: string;
    site_id: string;
  }>({
    command: cronJob?.command || '',
    user: cronJob?.user || '',
    frequency: cronJob ? (page.props.configs.cronjob_intervals[cronJob.frequency] ? cronJob.frequency : 'custom') : '',
    custom: cronJob?.frequency || '',
    site_id: cronJob?.site_id?.toString() || '0',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (cronJob) {
      const routeName = site ? 'cronjobs.site.update' : 'cronjobs.update';
      const routeParams = site ? { server: serverId, site: site.id, cronJob: cronJob.id } : { server: serverId, cronJob: cronJob.id };

      form.put(route(routeName, routeParams), {
        onSuccess: () => {
          setOpen(false);
          form.reset();
        },
      });
      return;
    }

    const routeName = site ? 'cronjobs.site.store' : 'cronjobs.store';
    const routeParams = site ? { server: serverId, site: site.id } : { server: serverId };

    form.post(route(routeName, routeParams), {
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
          <DialogTitle>{cronJob ? 'Edit' : 'Create'} cron job</DialogTitle>
          <DialogDescription className="sr-only">{cronJob ? 'Edit' : 'Create new'} cron job</DialogDescription>
        </DialogHeader>
        <Form id="cronjob-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="command">Command</Label>
              <Input type="text" id="command" value={form.data.command} onChange={(e) => form.setData('command', e.target.value)} />
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

            {/*frequency*/}
            <FormField>
              <Label htmlFor="frequency">Frequency</Label>
              <Select value={form.data.frequency} onValueChange={(value) => form.setData('frequency', value)}>
                <SelectTrigger id="frequency">
                  <SelectValue placeholder="Select a frequency" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    {Object.entries(page.props.configs.cronjob_intervals).map(([key, value]) => (
                      <SelectItem key={`frequency-${key}`} value={key}>
                        {value}
                      </SelectItem>
                    ))}
                  </SelectGroup>
                </SelectContent>
              </Select>
              <InputError message={form.errors.frequency} />
            </FormField>

            {/*custom frequency*/}
            {form.data.frequency === 'custom' && (
              <FormField>
                <Label htmlFor="custom_frequency">Custom frequency (crontab)</Label>
                <Input
                  id="custom_frequency"
                  name="custom_frequency"
                  value={form.data.custom}
                  onChange={(e) => form.setData('custom', e.target.value)}
                  placeholder="* * * * *"
                />
                <InputError message={form.errors.custom} />
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
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Close</Button>
          </DialogClose>
          <Button form="cronjob-form" type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
