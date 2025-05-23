import React, { FormEvent, ReactNode, useState } from 'react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { LoaderCircle } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/ui/input-error';
import { SharedData } from '@/types';
import { Input } from '@/components/ui/input';
import { Backup } from '@/types/backup';
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

export default function EditBackup({ backup, children }: { backup: Backup; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const page = usePage<SharedData>();

  const form = useForm<{
    interval: string;
    custom_interval: string;
    keep: string;
  }>({
    interval: page.props.configs.cronjob_intervals[backup.interval] ? backup.interval : 'custom',
    custom_interval: backup.interval,
    keep: backup.keep_backups.toString(),
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.patch(route('backups.update', { server: backup.server_id, backup: backup.id }), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Create backup</DialogTitle>
          <DialogDescription className="sr-only">Create a new backup</DialogDescription>
        </DialogHeader>
        <Form id="create-backup-form" onSubmit={submit} className="p-4">
          <FormFields>
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
        <DialogFooter>
          <div className="flex items-center gap-2">
            <DialogClose asChild>
              <Button variant="outline">Cancel</Button>
            </DialogClose>
            <Button form="create-backup-form" type="button" onClick={submit} disabled={form.processing}>
              {form.processing && <LoaderCircle className="animate-spin" />}
              Save
            </Button>
          </div>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
