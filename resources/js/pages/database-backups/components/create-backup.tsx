import { Server } from '@/types/server';
import React, { FormEvent, ReactNode, useState } from 'react';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { LoaderCircle, WifiIcon } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Database } from '@/types/database';
import axios from 'axios';
import InputError from '@/components/ui/input-error';
import { StorageProvider } from '@/types/storage-provider';
import ConnectStorageProvider from '@/pages/storage-providers/components/connect-storage-provider';
import { SharedData } from '@/types';
import { Input } from '@/components/ui/input';

export default function CreateBackup({ server, children }: { server: Server; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const [databases, setDatabases] = useState<Database[]>([]);
  const [storageProviders, setStorageProviders] = useState<StorageProvider[]>([]);
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
    form.post(route('database-backups.store', { server: server.id }), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };

  const onOpenChange = (open: boolean) => {
    setOpen(open);
    if (open) {
      fetchDatabases();
      fetchStorageProviders();
    }
  };

  const fetchDatabases = () => {
    axios.get(route('databases.json', { server: server.id })).then((response) => {
      setDatabases(response.data);
    });
  };

  const fetchStorageProviders = () => {
    axios.get(route('storage-providers.json')).then((response) => {
      setStorageProviders(response.data);
    });
  };

  return (
    <Sheet open={open} onOpenChange={onOpenChange}>
      <SheetTrigger asChild>{children}</SheetTrigger>
      <SheetContent className="sm:max-w-3xl">
        <SheetHeader>
          <SheetTitle>Create backup</SheetTitle>
          <SheetDescription className="sr-only">Create a new backup</SheetDescription>
        </SheetHeader>
        <Form id="create-backup" onSubmit={submit} className="p-4">
          <FormFields>
            {/*database*/}
            <FormField>
              <Label htmlFor="database">Database</Label>
              <Select value={form.data.database} onValueChange={(value) => form.setData('database', value)}>
                <SelectTrigger id="database">
                  <SelectValue placeholder="Select a database" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    {databases.map((database) => (
                      <SelectItem key={`db-${database.name}`} value={database.id.toString()}>
                        {database.name}
                      </SelectItem>
                    ))}
                  </SelectGroup>
                </SelectContent>
              </Select>
              <InputError message={form.errors.database} />
            </FormField>

            {/*storage*/}
            <FormField>
              <Label htmlFor="storage">Storage</Label>
              <div className="flex items-center gap-2">
                <Select value={form.data.storage} onValueChange={(value) => form.setData('storage', value)}>
                  <SelectTrigger id="storage">
                    <SelectValue placeholder="Select a storage" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      {storageProviders.map((storageProvider) => (
                        <SelectItem key={`sp-${storageProvider.name}`} value={storageProvider.id.toString()}>
                          {storageProvider.name}
                        </SelectItem>
                      ))}
                    </SelectGroup>
                  </SelectContent>
                </Select>
                <ConnectStorageProvider onProviderAdded={() => fetchStorageProviders()}>
                  <Button variant="outline">
                    <WifiIcon />
                  </Button>
                </ConnectStorageProvider>
              </div>
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
            <Button type="button" onClick={submit} disabled={form.processing}>
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
