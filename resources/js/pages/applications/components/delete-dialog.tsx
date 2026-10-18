import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DialogControlProps } from '@/components/dialogs/registry';
import { Form, FormField } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { LoaderCircleIcon } from 'lucide-react';
import { Application, Environment } from '@/types/application';

export default function DeleteDialog({ open, onOpenChange, application, environment }: DialogControlProps & {
  application: Application;
  environment?: Environment;
}) {
  const target = environment ?? application;
  const form = useForm({ name: '' });
  const submit = (e: FormEvent) => {
    e.preventDefault();
    const url = environment ? `/applications/${application.id}/environments/${environment.id}` : `/applications/${application.id}`;
    form.delete(url, { onSuccess: () => onOpenChange(false) });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Delete {target.name}</DialogTitle>
          <DialogDescription>This tears down all components before permanently deleting the {environment ? 'environment' : 'application and its environments'}. Failed cleanup can be retried. This cannot be undone.</DialogDescription>
        </DialogHeader>
        <Form id="delete-application-resource-form" className="p-4" onSubmit={submit}>
          <FormField>
            <Label htmlFor="confirm-name">Type {target.name} to confirm</Label>
            <Input id="confirm-name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} autoComplete="off" />
            <InputError message={form.errors.name} />
          </FormField>
        </Form>
        <DialogFooter>
          <DialogClose asChild><Button variant="outline">Cancel</Button></DialogClose>
          <Button form="delete-application-resource-form" type="submit" variant="destructive" disabled={form.processing || form.data.name !== target.name}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}Delete
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
