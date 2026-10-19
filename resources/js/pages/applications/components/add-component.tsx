import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DialogControlProps } from '@/components/dialogs/registry';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/ui/input-error';
import { LoaderCircleIcon } from 'lucide-react';
import { Application, ComponentOptions, Environment } from '@/types/application';
import { CapabilityConfiguration } from '@/types/application';
import CapabilityFields from './capability-fields';

export default function AddComponent({ open, onOpenChange, application, environment, options }: DialogControlProps & {
  application: Application;
  environment: Environment;
  options: ComponentOptions;
}) {
  const form = useForm({
    name: '',
    server_id: '',
    user: '',
    type: 'app',
    capabilities: [] as CapabilityConfiguration[],
  });
  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(`/applications/${application.id}/environments/${environment.id}/components`, { onSuccess: () => onOpenChange(false) });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Add component</DialogTitle>
          <DialogDescription>Place an app or load balancer in {environment.name}. Apps join the live release after installation. Load balancers need only a supported webserver.</DialogDescription>
        </DialogHeader>
        <Form id="add-component-form" className="p-4" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="component-name">Name</Label>
              <Input id="component-name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
              <InputError message={form.errors.name} />
            </FormField>
            <FormField>
              <Label htmlFor="component-type">Type</Label>
              <Select value={form.data.type} onValueChange={(type) => form.setData((data) => ({ ...data, type, capabilities: type === 'load_balancer' ? [] : data.capabilities, user: type === 'load_balancer' ? options.servers.find((server) => String(server.id) === data.server_id)?.ssh_user ?? '' : data.user }))}>
                <SelectTrigger id="component-type"><SelectValue /></SelectTrigger>
                <SelectContent><SelectItem value="app">App</SelectItem><SelectItem value="load_balancer">Load balancer</SelectItem></SelectContent>
              </Select>
              <InputError message={form.errors.type} />
            </FormField>
            <FormField>
              <Label htmlFor="component-server">Server</Label>
              <Select value={form.data.server_id} onValueChange={(value) => form.setData((data) => ({ ...data, server_id: value, user: data.type === 'load_balancer' || !data.user || data.user === options.servers.find((server) => String(server.id) === data.server_id)?.ssh_user ? options.servers.find((server) => String(server.id) === value)?.ssh_user ?? '' : data.user }))}>
                <SelectTrigger id="component-server"><SelectValue placeholder="Select a ready server" /></SelectTrigger>
                <SelectContent>{options.servers.map((server) => <SelectItem key={server.id} value={String(server.id)}>{server.name}</SelectItem>)}</SelectContent>
              </Select>
              <InputError message={form.errors.server_id} />
            </FormField>
            <FormField>
              <Label htmlFor="component-user">Linux user</Label>
              <Input disabled={form.data.type === 'load_balancer'} id="component-user" value={form.data.user} onChange={(e) => form.setData('user', e.target.value)} autoComplete="off" />
              <p className="text-muted-foreground text-sm">{form.data.type === 'load_balancer' ? 'Load balancers use the server SSH user and do not provision app directories, tooling or deploy keys.' : 'Use the server SSH user or an isolated user. Required services must already be installed.'}</p>
              <InputError message={form.errors.user} />
            </FormField>
            {form.data.type === 'app' && <CapabilityFields options={options.capabilities} value={form.data.capabilities} onChange={(value) => form.setData('capabilities', value)} errors={form.errors} />}
            {form.data.type !== 'app' && <InputError message={form.errors.capabilities} />}
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild><Button variant="outline">Cancel</Button></DialogClose>
          <Button form="add-component-form" type="submit" disabled={form.processing || !form.data.server_id}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}Add component
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
