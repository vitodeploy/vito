import { FormEvent, useState } from 'react';
import { useForm } from '@inertiajs/react';
import * as ToggleGroup from '@radix-ui/react-toggle-group';
import { ChevronRightIcon, LoaderCircleIcon } from 'lucide-react';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DialogControlProps } from '@/components/dialogs/registry';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { cn } from '@/lib/utils';
import { useConfigs } from '@/stores/bootstrap-store';
import { SourceControl } from '@/types/source-control';
import { EnvironmentFormData } from '@/types/application';
import ApplicationFields, { ApplicationFormData, usableSourceControls } from './application-fields';
import EnvironmentFields, { environmentDefaults } from './environment-fields';

export default function CreateApplication({ open, onOpenChange, sourceControls }: DialogControlProps & { sourceControls: SourceControl[] }) {
  const configs = useConfigs()!;
  const runtimes = configs.application.runtimes;
  const available = usableSourceControls(sourceControls, configs);
  const [customize, setCustomize] = useState(false);
  const form = useForm<ApplicationFormData & { runtime: string; environment: EnvironmentFormData }>({
    name: '', repository: '', source_control_id: available.length === 1 ? String(available[0].id) : '', runtime: '', environment: environmentDefaults(),
  });
  const preset = runtimes.find((runtime) => runtime.id === form.data.runtime);
  const environmentErrors = Object.fromEntries(
    Object.entries(form.errors).filter(([key]) => key.startsWith('environment.')).map(([key, value]) => [key.slice('environment.'.length), value]),
  );
  const change = (name: keyof ApplicationFormData, value: string) => form.setData((data) => {
    const derived = data.repository.split('/').pop() ?? '';
    return { ...data, [name]: value, ...(name === 'repository' && (!data.name || data.name === derived) ? { name: value.split('/').pop() ?? '' } : {}) };
  });
  const selectRuntime = (runtime: string) => {
    const defaults = environmentDefaults(runtimes.find((item) => item.id === runtime));
    form.setData((data) => ({ ...data, runtime, environment: { ...defaults, name: data.environment.name, branch: data.environment.branch } }));
    form.clearErrors('runtime', 'environment.runtime_version');
  };
  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post('/applications', { onSuccess: () => onOpenChange(false) });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>New application</DialogTitle>
          <DialogDescription>Connect a repository. You'll pick servers on the next screen.</DialogDescription>
        </DialogHeader>
        <Form id="create-application-form" className="p-4" onSubmit={submit}>
          <FormFields>
            <ApplicationFields data={form.data} errors={form.errors} onChange={change} sourceControls={sourceControls} />
            <FormField>
              <Label id="application-runtime">Runtime</Label>
              <ToggleGroup.Root
                type="single"
                aria-labelledby="application-runtime"
                value={form.data.runtime}
                onValueChange={(runtime) => runtime && selectRuntime(runtime)}
                className="grid grid-cols-2 gap-2 sm:grid-cols-3"
              >
                {runtimes.map((runtime) => (
                  <ToggleGroup.Item
                    key={runtime.id}
                    value={runtime.id}
                    className="hover:bg-accent focus-visible:ring-ring/50 data-[state=on]:border-primary data-[state=on]:bg-accent data-[state=on]:ring-primary/30 rounded-lg border px-3 py-2.5 text-left text-sm font-medium transition-colors outline-none focus-visible:ring-[3px] data-[state=on]:ring-2"
                  >
                    {runtime.label}
                  </ToggleGroup.Item>
                ))}
              </ToggleGroup.Root>
              <p className="text-muted-foreground text-xs">The runtime can't be changed later.</p>
              <InputError message={form.errors.runtime} />
            </FormField>
            <div className="flex flex-col gap-4 rounded-lg border p-3">
              <button type="button" className="flex items-center justify-between gap-2 text-left text-sm" onClick={() => setCustomize((value) => !value)} aria-expanded={customize}>
                <span>
                  <span className="font-medium">First environment</span>
                  <span className="text-muted-foreground"> · {form.data.environment.name} from <span className="font-mono">{form.data.environment.branch}</span>{form.data.environment.runtime_version ? ` · ${preset?.label} ${form.data.environment.runtime_version}` : ''}</span>
                </span>
                <span className="text-muted-foreground flex items-center gap-1 text-xs">Customize<ChevronRightIcon className={cn('size-4 transition-transform', customize && 'rotate-90')} /></span>
              </button>
              {(customize || Object.keys(environmentErrors).length > 0) && (
                <FormFields>
                  <EnvironmentFields
                    preset={preset}
                    data={form.data.environment}
                    errors={environmentErrors}
                    onChange={(name, value) => form.setData((data) => ({ ...data, environment: { ...data.environment, [name]: value } }))}
                  />
                </FormFields>
              )}
              <InputError message={form.errors.environment} />
            </div>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild><Button variant="outline">Cancel</Button></DialogClose>
          <Button form="create-application-form" type="submit" disabled={form.processing || !form.data.runtime || !form.data.repository}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}Create application
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
