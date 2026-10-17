import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DialogControlProps } from '@/components/dialogs/registry';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { LoaderCircleIcon } from 'lucide-react';
import { useConfigs } from '@/stores/bootstrap-store';
import { SourceControl } from '@/types/source-control';
import { EnvironmentFormData } from '@/types/application';
import ApplicationFields, { ApplicationFormData } from './application-fields';
import EnvironmentFields, { environmentDefaults } from './environment-fields';

export default function CreateApplication({ open, onOpenChange, sourceControls }: DialogControlProps & { sourceControls: SourceControl[] }) {
  const runtimes = useConfigs()!.application.runtimes;
  const form = useForm<ApplicationFormData & { runtime: string; environment: EnvironmentFormData }>({
    name: '', repository: '', source_control_id: '', runtime: '', environment: environmentDefaults(),
  });
  const preset = runtimes.find((runtime) => runtime.id === form.data.runtime);
  const environmentErrors = Object.fromEntries(
    Object.entries(form.errors).filter(([key]) => key.startsWith('environment.')).map(([key, value]) => [key.slice('environment.'.length), value]),
  );

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post('/applications', { onSuccess: () => onOpenChange(false) });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Create application</DialogTitle>
          <DialogDescription>Choose a repository and runtime preset, then create your first environment.</DialogDescription>
        </DialogHeader>
        <Form id="create-application-form" className="p-4" onSubmit={submit}>
          <FormFields>
            <ApplicationFields data={form.data} errors={form.errors} onChange={(name, value) => form.setData(name, value)} sourceControls={sourceControls} />
            <FormField>
              <Label htmlFor="application-runtime">Runtime preset</Label>
              <Select value={form.data.runtime} onValueChange={(runtime) => {
                const defaults = environmentDefaults(runtimes.find((item) => item.id === runtime));
                form.setData((data) => ({ ...data, runtime, environment: { ...defaults, name: data.environment.name, branch: data.environment.branch } }));
                form.clearErrors('runtime', 'environment.runtime_version');
              }}>
                <SelectTrigger id="application-runtime"><SelectValue placeholder="Select a runtime preset" /></SelectTrigger>
                <SelectContent>
                  {runtimes.map((runtime) => <SelectItem key={runtime.id} value={runtime.id}>{runtime.label}</SelectItem>)}
                </SelectContent>
              </Select>
              <p className="text-muted-foreground text-sm">The preset cannot be changed after creation.</p>
              <InputError message={form.errors.runtime} />
            </FormField>
            <EnvironmentFields
              preset={preset}
              data={form.data.environment}
              errors={environmentErrors}
              onChange={(name, value) => form.setData((data) => ({ ...data, environment: { ...data.environment, [name]: value } }))}
            />
            <InputError message={form.errors.environment} />
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild><Button variant="outline">Cancel</Button></DialogClose>
          <Button form="create-application-form" type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}Create application
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
