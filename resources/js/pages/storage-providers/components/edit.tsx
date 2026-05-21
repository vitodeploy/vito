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
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import FormSuccessful from '@/components/form-successful';
import { FormEvent, useEffect, useState } from 'react';
import InputError from '@/components/ui/input-error';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { StorageProvider } from '@/types/storage-provider';

export default function Edit({ storageProvider }: { storageProvider: StorageProvider }) {
  const [open, setOpen] = useState(false);
  const form = useForm({
    name: storageProvider.name,
    global: storageProvider.global,
  });

  useEffect(() => {
    form.setData({ name: storageProvider.name, global: storageProvider.global });
  }, [storageProvider.id, storageProvider.name, storageProvider.global]);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.patch(route('storage-providers.update', storageProvider.id), {
      onSuccess: () => setOpen(false),
    });
  };
  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Edit</DropdownMenuItem>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Edit {storageProvider.name}</DialogTitle>
          <DialogDescription className="sr-only">Edit storage provider</DialogDescription>
        </DialogHeader>
        <Form id={`edit-storage-provider-form-${storageProvider.id}`} className="p-4" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="name">Name</Label>
              <Input type="text" id="name" name="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
              <InputError message={form.errors.name} />
            </FormField>
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
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button form={`edit-storage-provider-form-${storageProvider.id}`} type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
