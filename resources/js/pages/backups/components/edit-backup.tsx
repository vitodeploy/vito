import { FormEvent } from 'react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { LoaderCircle } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Backup } from '@/types/backup';
import { useConfigs } from '@/stores/bootstrap-store';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';

export default function EditBackup({ open, onOpenChange, backup }: { open: boolean; onOpenChange: (open: boolean) => void; backup: Backup }) {
  const configs = useConfigs()!;

  const form = useForm<{
    interval: string;
    custom_interval: string;
    keep: string;
    prefix: string;
  }>({
    interval: configs.cronjob_intervals[backup.interval] ? backup.interval : 'custom',
    custom_interval: backup.interval,
    keep: backup.keep_backups.toString(),
    prefix: backup.prefix ?? '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.patch(`/servers/${backup.server_id}/backups/${backup.id}`, {
      onSuccess: () => onOpenChange(false),
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Edit backup</DialogTitle>
          <DialogDescription className="sr-only">Edit backup</DialogDescription>
        </DialogHeader>
        <Form id="edit-backup-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="prefix">File Name Prefix (optional)</Label>
              <Input
                id="prefix"
                name="prefix"
                value={form.data.prefix}
                onChange={(e) => form.setData('prefix', e.target.value)}
                placeholder="site-a"
              />
              <div className="text-muted-foreground mt-1 text-sm">Added to new backup file names. Existing files keep their names.</div>
              <InputError message={form.errors.prefix} />
            </FormField>

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
            <Button form="edit-backup-form" type="submit" disabled={form.processing}>
              {form.processing && <LoaderCircle className="animate-spin" />}
              Save
            </Button>
          </div>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
