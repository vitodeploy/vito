import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogFooter, DialogClose } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Form, FormFields, FormField } from '@/components/ui/form';
import InputError from '@/components/ui/input-error';
import { useForm } from '@inertiajs/react';
import { FormEventHandler, useEffect } from 'react';
import { VitoBackup } from '@/types';
import { LoaderCircle } from 'lucide-react';

interface EditVitoBackupDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  backup: VitoBackup;
}

export default function EditVitoBackupDialog({ open, onOpenChange, backup }: EditVitoBackupDialogProps) {
  const form = useForm<{
    frequency: string;
    keep_backups: number;
  }>({
    frequency: backup.frequency,
    keep_backups: backup.keep_backups,
  });

  useEffect(() => {
    if (open) {
      form.setData({
        frequency: backup.frequency,
        keep_backups: backup.keep_backups,
      });
    }
  }, [open, backup]);

  const submit: FormEventHandler = (e) => {
    e.preventDefault();
    form.put(route('vito-backups.update', { vitoBackup: backup.id }), {
      onSuccess: () => {
        onOpenChange(false);
      },
      onError: (errors) => {
        console.error('Form submission errors:', errors);
      },
    });
  };

  const frequencyOptions = [
    { value: '0 * * * *', label: 'Hourly' },
    { value: '0 0 * * *', label: 'Daily' },
    { value: '0 0 * * 0', label: 'Weekly' },
    { value: '0 0 1 * *', label: 'Monthly' },
  ];

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Edit Vito Backup</DialogTitle>
          <DialogDescription className="sr-only">Update the backup configuration settings.</DialogDescription>
        </DialogHeader>

        <Form id="edit-vito-backup-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="frequency">Frequency</Label>
              <Select value={form.data.frequency} onValueChange={(value) => form.setData('frequency', value)}>
                <SelectTrigger>
                  <SelectValue placeholder="Select frequency" />
                </SelectTrigger>
                <SelectContent>
                  {frequencyOptions.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                      {option.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <InputError message={form.errors.frequency} />
            </FormField>

            <FormField>
              <Label htmlFor="keep_backups">Keep Backups</Label>
              <Input
                id="keep_backups"
                type="number"
                min="1"
                max="100"
                value={form.data.keep_backups}
                onChange={(e) => form.setData('keep_backups', parseInt(e.target.value))}
                required
              />
              <InputError message={form.errors.keep_backups} />
            </FormField>

            <FormField>
              <Label htmlFor="storage_id">Storage Provider</Label>
              <Input id="storage_id" value={`${backup.storage?.profile} (${backup.storage?.provider})`} disabled className="bg-muted" />
              <p className="text-muted-foreground text-sm">Storage provider cannot be changed after backup creation</p>
            </FormField>
          </FormFields>
        </Form>

        <DialogFooter>
          <div className="flex items-center gap-2">
            <Button form="edit-vito-backup-form" type="submit" disabled={form.processing}>
              {form.processing && <LoaderCircle className="animate-spin" />}
              Update Backup
            </Button>
            <DialogClose asChild>
              <Button variant="outline">Cancel</Button>
            </DialogClose>
          </div>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
