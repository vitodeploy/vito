import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DialogControlProps } from '@/components/dialogs/registry';
import { Form, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import InputError from '@/components/ui/input-error';
import { LoaderCircleIcon } from 'lucide-react';
import { Application, Component, Environment, PrimaryCandidate } from '@/types/application';
import PrimaryComponentPicker from './primary-component-picker';

export default function DeleteComponent({ open, onOpenChange, application, environment, component, primaryCandidates }: DialogControlProps & {
  application: Application;
  environment: Environment;
  component: Component;
  primaryCandidates: PrimaryCandidate[];
}) {
  const candidates = primaryCandidates.filter((candidate) => candidate.id !== component.id);
  const primary = environment.primary_component_id === component.id;
  const form = useForm({ primary_component_id: '' });
  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.delete(`/applications/${application.id}/environments/${environment.id}/components/${component.id}`, { onSuccess: () => onOpenChange(false) });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Delete {component.name}</DialogTitle>
          <DialogDescription>This removes its deploy key, files and any unshared isolated user. It cannot be undone.</DialogDescription>
        </DialogHeader>
        <Form id="delete-component-form" className="p-4" onSubmit={submit}>
          <FormFields>
            {primary && <>
              <p className="text-sm">This is the primary component. {candidates[0] ? `Consider ${candidates[0].name} as its replacement.` : 'No replacement is available.'} Leaving it unset blocks deployments until you select a primary.</p>
              <PrimaryComponentPicker value={form.data.primary_component_id} onChange={(value) => form.setData('primary_component_id', value)} candidates={candidates} error={form.errors.primary_component_id} />
            </>}
            <InputError message={form.errors.primary_component_id && !primary ? form.errors.primary_component_id : undefined} />
            <InputError message={(form.errors as Record<string, string>).component} />
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild><Button variant="outline">Cancel</Button></DialogClose>
          <Button form="delete-component-form" type="submit" variant="destructive" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}Delete
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
