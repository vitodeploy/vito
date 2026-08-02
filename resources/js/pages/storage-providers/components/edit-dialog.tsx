import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import FormSuccessful from '@/components/form-successful';
import { FormEvent } from 'react';
import InputError from '@/components/ui/input-error';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { StorageProvider } from '@/types/storage-provider';
import DynamicField from '@/components/ui/dynamic-field';
import { DynamicFieldConfig, DynamicFieldValue } from '@/types/dynamic-field-config';
import { useConfigs } from '@/stores/bootstrap-store';

export default function StorageProviderEditDialog({
  open,
  onOpenChange,
  storageProvider,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  storageProvider: StorageProvider;
}) {
  const configs = useConfigs()!;
  const editFields: DynamicFieldConfig[] = configs.storage_provider.providers[storageProvider.provider]?.edit_form ?? [];

  const form = useForm<{ name: string; global: boolean } & Record<string, DynamicFieldValue>>({
    ...Object.fromEntries(editFields.map((field) => [field.name, storageProvider.editable_data?.[field.name] ?? ''])),
    name: storageProvider.name,
    global: storageProvider.global,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.patch(route('storage-providers.update', storageProvider.id), {
      onSuccess: () => onOpenChange(false),
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-h-screen overflow-y-auto sm:max-w-xl" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Edit {storageProvider.name}</DialogTitle>
          <DialogDescription className="sr-only">Edit storage provider</DialogDescription>
        </DialogHeader>
        <Form id="edit-storage-provider-form" className="p-4" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="name">Name</Label>
              <Input type="text" id="name" name="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
              <InputError message={form.errors.name} />
            </FormField>
            {editFields.map((field) => (
              <DynamicField
                key={`field-${field.name}`}
                value={form.data[field.name]}
                onChange={(value) => form.setData(field.name, value)}
                config={field}
                error={form.errors[field.name]}
              />
            ))}
            <FormField>
              <div className="flex items-center space-x-3">
                <Checkbox
                  id="global"
                  name="global"
                  checked={form.data.global}
                  onCheckedChange={(checked) => form.setData('global', Boolean(checked))}
                />
                <Label htmlFor="global">Is global (accessible in all projects)</Label>
              </div>
              <InputError message={form.errors.global} />
            </FormField>
            <FormField>
              <InputError message={form.errors.provider} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button form="edit-storage-provider-form" type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
