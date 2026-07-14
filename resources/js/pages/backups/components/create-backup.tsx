import { Server } from '@/types/server';
import { FormEvent, useState } from 'react';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle } from '@/components/ui/sheet';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { LoaderCircle } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { useConfigs } from '@/stores/bootstrap-store';
import StorageProviderSelect from '@/pages/storage-providers/components/storage-provider-select';
import DatabaseSelect from '@/pages/databases/components/database-select';
import ServerSelect from '@/pages/servers/components/server-select';

export default function CreateBackup({ open, onOpenChange, server }: { open: boolean; onOpenChange: (open: boolean) => void; server?: Server }) {
  const configs = useConfigs()!;
  const [selectedServer, setSelectedServer] = useState<Server | undefined>(undefined);
  const activeServer = server ?? selectedServer;

  const form = useForm<{
    type: string;
    database: string;
    path: string;
    storage: string;
    interval: string;
    custom_interval: string;
    keep: string;
  }>({
    type: 'file',
    database: '',
    path: '',
    storage: '',
    interval: 'daily',
    custom_interval: '',
    keep: '10',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (!activeServer) {
      return;
    }
    form.post(route('backups.store', { server: activeServer.id }), {
      onSuccess: () => onOpenChange(false),
    });
  };

  return (
    <Sheet open={open} onOpenChange={onOpenChange}>
      <SheetContent className="sm:max-w-3xl" onCloseAutoFocus={(e) => e.preventDefault()}>
        <SheetHeader>
          <SheetTitle>Create backup</SheetTitle>
          <SheetDescription className="sr-only">Create a new backup</SheetDescription>
        </SheetHeader>
        <Form id="create-backup-form" onSubmit={submit} className="p-4">
          <FormFields>
            {/*server - only when not opened from a server page*/}
            {!server && (
              <FormField>
                <Label htmlFor="server">Server</Label>
                <ServerSelect
                  id="server"
                  value={selectedServer ? String(selectedServer.id) : ''}
                  onValueChange={(value) => {
                    setSelectedServer(value);
                    if (!value?.services?.database) {
                      form.setData('type', 'file');
                    }
                    form.setData('database', '');
                  }}
                />
              </FormField>
            )}

            {/*backup type*/}
            <FormField>
              <Label htmlFor="type">Backup Type</Label>
              <Select value={form.data.type} onValueChange={(value) => form.setData('type', value)}>
                <SelectTrigger id="type">
                  <SelectValue placeholder="Select backup type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    <SelectItem value="file">File Backup</SelectItem>
                    {activeServer?.services?.database && <SelectItem value="database">Database Backup</SelectItem>}
                  </SelectGroup>
                </SelectContent>
              </Select>
              <InputError message={form.errors.type} />
            </FormField>

            {/*database - only show for database backups*/}
            {form.data.type === 'database' && activeServer && (
              <FormField>
                <Label htmlFor="database">Database</Label>
                <DatabaseSelect
                  id="database"
                  name="database"
                  serverId={activeServer.id}
                  value={form.data.database}
                  onValueChange={(value) => form.setData('database', value)}
                />
                <InputError message={form.errors.database} />
              </FormField>
            )}

            {/*path - only show for file backups*/}
            {form.data.type === 'file' && (
              <FormField>
                <Label htmlFor="path">File/Directory Path</Label>
                <Input
                  id="path"
                  name="path"
                  value={form.data.path}
                  onChange={(e) => form.setData('path', e.target.value)}
                  placeholder="/var/www/html or /home/user/documents"
                />
                <div className="text-muted-foreground mt-1 text-sm">Specify the file or directory path to backup.</div>
                <InputError message={form.errors.path} />
              </FormField>
            )}

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
                    {Object.entries(configs.cronjob_intervals).map(([key, value]) => (
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
            <Button form="create-backup-form" type="submit" disabled={form.processing || !activeServer}>
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
