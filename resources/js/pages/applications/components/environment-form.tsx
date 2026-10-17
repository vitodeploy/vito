import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DialogControlProps } from '@/components/dialogs/registry';
import { Form, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon } from 'lucide-react';
import { useConfigs } from '@/stores/bootstrap-store';
import { Application, Environment, EnvironmentFormData } from '@/types/application';
import EnvironmentFields, { environmentDefaults } from './environment-fields';

export default function EnvironmentForm({ open, onOpenChange, application, environment }: DialogControlProps & {
  application: Application;
  environment?: Environment;
}) {
  const preset = useConfigs()!.application.runtimes.find((runtime) => runtime.id === application.runtime);
  const form = useForm<EnvironmentFormData>({
    ...environmentDefaults(preset),
    name: environment?.name ?? '',
    branch: environment?.branch ?? 'main',
    runtime_version: environment?.runtime_version ?? environmentDefaults(preset).runtime_version,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const options = { onSuccess: () => onOpenChange(false) };
    if (environment) {
      form.put(`/applications/${application.id}/environments/${environment.id}`, options);
    } else {
      form.post(`/applications/${application.id}/environments`, options);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>{environment ? 'Edit environment' : 'Create environment'}</DialogTitle>
          <DialogDescription>
            {environment ? 'Changes apply on the next deploy. The slug remains unchanged.' : `Create an environment for ${application.name}.`}
          </DialogDescription>
        </DialogHeader>
        <Form id="environment-form" className="p-4" onSubmit={submit}>
          <FormFields>
            {!preset && <p className="text-destructive text-sm">This runtime preset is unavailable. Restore its plugin before editing environments.</p>}
            <EnvironmentFields preset={preset} data={form.data} errors={form.errors} onChange={(name, value) => form.setData(name, value)} />
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild><Button variant="outline">Cancel</Button></DialogClose>
          <Button form="environment-form" type="submit" disabled={form.processing || !preset}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}{environment ? 'Save' : 'Create environment'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
