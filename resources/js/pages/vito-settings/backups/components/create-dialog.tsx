import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogFooter, DialogClose } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Form, FormFields, FormField } from '@/components/ui/form';
import InputError from '@/components/ui/input-error';
import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';
import { StorageProvider } from '@/types/storage-provider';
import { LoaderCircle } from 'lucide-react';

interface CreateVitoBackupDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  storageProviders: StorageProvider[];
}

export default function CreateVitoBackupDialog({ open, onOpenChange, storageProviders }: CreateVitoBackupDialogProps) {
  const form = useForm<{
    frequency: string;
    keep_backups: number;
    storage_id: number;
  }>({
    frequency: '',
    keep_backups: 5,
    storage_id: 0,
  });

  const submit: FormEventHandler = (e) => {
    e.preventDefault();
    form.post(route('vito-backups.store'), {
      onSuccess: () => {
        onOpenChange(false);
        form.reset();
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
          <DialogTitle>Create Vito Backup</DialogTitle>
          <DialogDescription className="sr-only">
            Configure an automated backup that will store Vito data in your selected storage provider.
          </DialogDescription>
        </DialogHeader>

        <Form id="create-vito-backup-form" onSubmit={submit} className="p-4">
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
              <Select value={form.data.storage_id?.toString() || ''} onValueChange={(value) => form.setData('storage_id', parseInt(value))}>
                <SelectTrigger>
                  <SelectValue placeholder="Select storage provider" />
                </SelectTrigger>
                <SelectContent>
                  {storageProviders.map((provider) => (
                    <SelectItem key={provider.id} value={provider.id.toString()}>
                      {`${provider.profile} (${provider.provider})`}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <p className="text-muted-foreground text-sm">
                Only global storage providers are available for Vito backups. Project-specific storage providers cannot be used.
              </p>
              <InputError message={form.errors.storage_id} />
            </FormField>
          </FormFields>
        </Form>

        <DialogFooter>
          <div className="flex items-center gap-2">
            <Button form="create-vito-backup-form" type="button" onClick={submit} disabled={form.processing}>
              {form.processing && <LoaderCircle className="animate-spin" />}
              Create Backup
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
