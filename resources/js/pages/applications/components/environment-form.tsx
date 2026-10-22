import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DialogControlProps } from '@/components/dialogs/registry';
import { Form, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import InputError from '@/components/ui/input-error';
import { useConfigs } from '@/stores/bootstrap-store';
import { Application, EnvironmentFormData } from '@/types/application';
import EnvironmentFields, { environmentDefaults } from './environment-fields';

export default function EnvironmentForm({ open, onOpenChange, application }: DialogControlProps & { application: Application }) {
  const preset = useConfigs()!.application.runtimes.find((runtime) => runtime.id === application.runtime);
  const form = useForm<EnvironmentFormData>({ ...environmentDefaults(preset), name: '' });
  const known = ['name', 'branch', 'runtime_version', ...(preset?.form ?? []).map((field) => field.name)];
  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(`/applications/${application.id}/environments`, { onSuccess: () => onOpenChange(false) });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>New environment</DialogTitle>
          <DialogDescription>Deploy another branch of {application.name}, e.g. staging.</DialogDescription>
        </DialogHeader>
        <Form id="environment-form" className="p-4" onSubmit={submit}>
          <FormFields>
            {!preset && <p className="text-destructive text-sm">The {application.runtime} runtime plugin is missing. Restore it to create environments.</p>}
            <EnvironmentFields preset={preset} data={form.data} errors={form.errors} onChange={(name, value) => form.setData(name, value)} />
            {Object.entries(form.errors).filter(([key]) => !known.includes(key)).map(([key, message]) => <InputError key={key} message={message} />)}
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild><Button variant="outline">Cancel</Button></DialogClose>
          <Button form="environment-form" type="submit" disabled={form.processing || !preset}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}Create environment
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
