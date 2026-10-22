import { FormEvent } from 'react';
import { Link, useForm } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DialogControlProps } from '@/components/dialogs/registry';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/ui/input-error';
import { Application, CapabilityConfiguration, ComponentOptions, Environment } from '@/types/application';
import CapabilityFields from './capability-fields';
import { Field } from './section';

export default function AddComponent({ open, onOpenChange, application, environment, options, type }: DialogControlProps & {
  application: Application;
  environment: Environment;
  options: ComponentOptions;
  type: 'app' | 'load_balancer';
}) {
  const loadBalancer = type === 'load_balancer';
  const server = options.servers.length === 1 ? options.servers[0] : undefined;
  const web = options.capabilities.find((capability) => capability.id === 'web');
  const form = useForm({
    name: loadBalancer ? 'load-balancer' : 'web',
    server_id: server ? String(server.id) : '',
    user: server?.ssh_user ?? '',
    type,
    capabilities: (!loadBalancer && web ? [{ capability: web.id, config: Object.fromEntries(web.form.map((field) => [field.name, field.default ?? ''])) }] : []) as CapabilityConfiguration[],
  });
  const sshUser = (id: string) => options.servers.find((item) => String(item.id) === id)?.ssh_user ?? '';
  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(`/applications/${application.id}/environments/${environment.id}/components`, { onSuccess: () => onOpenChange(false) });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>{loadBalancer ? 'Add load balancer' : 'Add app component'}</DialogTitle>
          <DialogDescription>
            {loadBalancer ? 'Spreads traffic across your app components. Needs a server with a webserver.' : `Runs ${application.name} on a server. It joins the live release once installed.`}
          </DialogDescription>
        </DialogHeader>
        <Form id="add-component-form" className="p-4" onSubmit={submit}>
          <FormFields>
            <Field
              id="component-server"
              label="Server"
              error={form.errors.server_id}
              description={options.servers.length === 0 && <>No ready servers. <Link href="/servers" className="underline">Add a server</Link> first.</>}
            >
              <Select value={form.data.server_id} onValueChange={(value) => form.setData((data) => ({ ...data, server_id: value, user: loadBalancer || !data.user || data.user === sshUser(data.server_id) ? sshUser(value) : data.user }))}>
                <SelectTrigger id="component-server"><SelectValue placeholder="Select a server" /></SelectTrigger>
                <SelectContent>{options.servers.map((item) => <SelectItem key={item.id} value={String(item.id)}>{item.name}</SelectItem>)}</SelectContent>
              </Select>
            </Field>
            <Field id="component-name" label="Name" error={form.errors.name}>
              <Input id="component-name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
            </Field>
            {!loadBalancer && (
              <Field id="component-user" label="Run as" description="The server's SSH user, or a new name to create an isolated user." error={form.errors.user}>
                <Input id="component-user" value={form.data.user} onChange={(e) => form.setData('user', e.target.value)} autoComplete="off" className="font-mono" />
              </Field>
            )}
            {!loadBalancer && (
              <FormField>
                <Label>Capabilities</Label>
                <div className="divide-y rounded-lg border">
                  <CapabilityFields options={options.capabilities} value={form.data.capabilities} onChange={(value) => form.setData('capabilities', value)} errors={form.errors} />
                </div>
              </FormField>
            )}
            <InputError message={form.errors.type ?? (loadBalancer ? form.errors.capabilities : undefined)} />
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild><Button variant="outline">Cancel</Button></DialogClose>
          <Button form="add-component-form" type="submit" disabled={form.processing || !form.data.server_id}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}{loadBalancer ? 'Add load balancer' : 'Add component'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
