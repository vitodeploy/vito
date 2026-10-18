import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DialogControlProps } from '@/components/dialogs/registry';
import { Form } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon } from 'lucide-react';
import { Application, Component, ComponentOptions, Environment } from '@/types/application';
import CapabilityFields from './capability-fields';

export default function CapabilitiesDialog({ open, onOpenChange, application, environment, component, options }: DialogControlProps & {
  application: Application;
  environment: Environment;
  component: Component;
  options: ComponentOptions;
}) {
  const form = useForm({ capabilities: component.capability_configs.map(({ capability, config }) => ({ capability, config })) });
  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.put(`/applications/${application.id}/environments/${environment.id}/components/${component.id}/capabilities`, { onSuccess: () => onOpenChange(false) });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl" onCloseAutoFocus={(event) => event.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Component capabilities</DialogTitle>
          <DialogDescription>Changes provision or remove server resources. Removing Worker also deletes its workers. Deploy again to apply configuration changes to the application.</DialogDescription>
        </DialogHeader>
        <Form id="component-capabilities-form" className="p-4" onSubmit={submit}>
          <CapabilityFields options={options.capabilities} value={form.data.capabilities} onChange={(value) => form.setData('capabilities', value)} errors={form.errors} />
        </Form>
        <DialogFooter>
          <DialogClose asChild><Button variant="outline">Cancel</Button></DialogClose>
          <Button form="component-capabilities-form" type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
