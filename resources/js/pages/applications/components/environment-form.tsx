import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DialogControlProps } from '@/components/dialogs/registry';
import { Form, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon } from 'lucide-react';
import { useConfigs } from '@/stores/bootstrap-store';
import { Application, Environment, EnvironmentFormData, PrimaryCandidate } from '@/types/application';
import EnvironmentFields, { environmentDefaults } from './environment-fields';
import PrimaryComponentPicker from './primary-component-picker';
import { Switch } from '@/components/ui/switch';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';

export default function EnvironmentForm({ open, onOpenChange, application, environment, primaryCandidates = [] }: DialogControlProps & {
  application: Application;
  environment?: Environment;
  primaryCandidates?: PrimaryCandidate[];
}) {
  const preset = useConfigs()!.application.runtimes.find((runtime) => runtime.id === application.runtime);
  const form = useForm<EnvironmentFormData>({
    ...environmentDefaults(preset),
    name: environment?.name ?? '',
    branch: environment?.branch ?? 'main',
    runtime_version: environment?.runtime_version ?? environmentDefaults(preset).runtime_version,
    primary_component_id: environment?.primary_component_id ? String(environment.primary_component_id) : '',
    auto_deploy: environment?.auto_deploy ?? false,
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
            {environment && <div className="flex flex-col gap-2">
              <div className="flex items-center gap-2">
                <Switch id="auto-deploy" checked={Boolean(form.data.auto_deploy)} onCheckedChange={(value) => form.setData('auto_deploy', value)} />
                <Label htmlFor="auto-deploy">Deploy automatically on push</Label>
              </div>
              <p className="text-muted-foreground text-sm">Pushes to this environment’s branch queue a deployment.</p>
              <InputError message={form.errors.auto_deploy} />
            </div>}
            {environment && <PrimaryComponentPicker value={String(form.data.primary_component_id)} onChange={(value) => form.setData('primary_component_id', value)} candidates={primaryCandidates} error={form.errors.primary_component_id} />}
            {Object.entries(form.errors).filter(([key]) => !['name', 'branch', 'runtime_version', ...(preset?.form ?? []).map((field) => field.name), ...(environment ? ['auto_deploy', 'primary_component_id'] : [])].includes(key)).map(([key, message]) => <InputError key={key} message={message} />)}
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
