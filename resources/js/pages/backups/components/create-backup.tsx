import { Server } from '@/types/server';
import React, { FormEvent, ReactNode, useState } from 'react';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { LoaderCircle } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/ui/input-error';
import { SharedData } from '@/types';
import { Input } from '@/components/ui/input';
import DatabaseSelect from '@/pages/database-users/components/database-select';
import StorageProviderSelect from '@/pages/storage-providers/components/storage-provider-select';

export default function CreateBackup({ server, children }: { server: Server; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const page = usePage<SharedData>();

  const form = useForm<{
    database: string;
    storage: string;
    interval: string;
    custom_interval: string;
    keep: string;
  }>({
    database: '',
    storage: '',
    interval: 'daily',
    custom_interval: '',
    keep: '10',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('backups.store', { server: server.id }), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };

  return (
    <Sheet open={open} onOpenChange={setOpen}>
      <SheetTrigger asChild>{children}</SheetTrigger>
      <SheetContent className="sm:max-w-3xl">
        <SheetHeader>
          <SheetTitle>Create backup</SheetTitle>
          <SheetDescription className="sr-only">Create a new backup</SheetDescription>
        </SheetHeader>
        <Form id="create-backup-form" onSubmit={submit} className="p-4">
          <FormFields>
            {/*database*/}
            <FormField>
              <Label htmlFor="database">Database</Label>
              <DatabaseSelect
                id="database"
                name="database"
                serverId={server.id}
                value={form.data.database}
                onValueChange={(value) => form.setData('database', value)}
              />
              <InputError message={form.errors.database} />
            </FormField>

            {/*storage*/}
            <FormField>
              <Label htmlFor="storage">Storage</Label>
              <StorageProviderSelect
                id="storage"
                name="storage"
                value={form.data.storage}
                onValueChange={(value) => form.setData('storage', value)}
              />
              <InputError message={form.errors.storage} />
            </FormField>

            {/*interval*/}
            <FormField>
              <Label htmlFor="interval">Interval</Label>
              <Select value={form.data.interval} onValueChange={(value) => form.setData('interval', value)}>
                <SelectTrigger id="interval">
                  <SelectValue placeholder="Select an interval" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    {Object.entries(page.props.configs.cronjob_intervals).map(([key, value]) => (
                      <SelectItem key={`interval-${key}`} value={key}>
                        {value}
                      </SelectItem>
                    ))}
                  </SelectGroup>
                </SelectContent>
              </Select>
              <InputError message={form.errors.interval} />
            </FormField>

            {/*custom interval*/}
            {form.data.interval === 'custom' && (
              <FormField>
                <Label htmlFor="custom_interval">Custom interval (crontab)</Label>
                <Input
                  id="custom_interval"
                  name="custom_interval"
                  value={form.data.custom_interval}
                  onChange={(e) => form.setData('custom_interval', e.target.value)}
                  placeholder="* * * * *"
                />
                <InputError message={form.errors.custom_interval} />
              </FormField>
            )}

            {/*backups to keep*/}
            <FormField>
              <Label htmlFor="keep">Backups to keep</Label>
              <Input id="keep" name="keep" value={form.data.keep} onChange={(e) => form.setData('keep', e.target.value)} />
              <InputError message={form.errors.keep} />
            </FormField>
          </FormFields>
        </Form>
        <SheetFooter>
          <div className="flex items-center gap-2">
            <Button form="create-backup-form" type="button" onClick={submit} disabled={form.processing}>
              {form.processing && <LoaderCircle className="animate-spin" />}
              Create
            </Button>
            <SheetClose asChild>
              <Button variant="outline">Cancel</Button>
            </SheetClose>
          </div>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}
