import { FormEvent } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import { DialogControlProps } from '@/components/dialogs/registry';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Application, Environment, EnvironmentResourceOptions } from '@/types/application';

export default function AttachResource({ open, onOpenChange, application, environment, options: initialOptions }: DialogControlProps & {
  application: Application;
  environment: Environment;
  options: EnvironmentResourceOptions;
}) {
  const page = usePage<{ application?: Application; environment?: Environment; resourceOptions?: EnvironmentResourceOptions }>().props;
  const options = page.application?.id === application.id && page.environment?.id === environment.id ? page.resourceOptions ?? initialOptions : initialOptions;
  const form = useForm({ mode: 'existing', service_id: '', server_id: '', database_id: '', name: '', charset: '', collation: '', alias: '' });
  const redis = form.data.mode === 'redis';
  const services = options.services.filter((service) => redis ? service.type === 'memory_database' : service.type === 'database');
  const service = services.find((item) => item.id.toString() === form.data.service_id);
  const selectService = (id: string) => {
    const selected = services.find((item) => item.id.toString() === id);
    const charset = selected?.default_charset ?? '';
    form.setData({ ...form.data, service_id: id, server_id: selected?.server_id.toString() ?? '', database_id: '', charset, collation: selected?.charsets[charset]?.default ?? '' });
  };
  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.transform((data) => redis ? { service_id: data.service_id, alias: data.alias } : data.mode === 'existing'
      ? { server_id: data.server_id, database_id: data.database_id, alias: data.alias }
      : { server_id: data.server_id, name: data.name, charset: data.charset, collation: data.collation, alias: data.alias });
    form.post(`/applications/${application.id}/environments/${environment.id}/resources/${redis ? 'redis' : 'database'}`, {
      onSuccess: () => onOpenChange(false),
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Attach resource</DialogTitle>
          <DialogDescription>Provide connection variables to every app component. Cross-server access requires a shared private network. The resource firewall must protect IPv4 and IPv6.</DialogDescription>
        </DialogHeader>
        <Form id="attach-resource-form" className="p-4" onSubmit={submit}>
          <FormFields>
            <Alert variant="destructive"><AlertDescription>Attaching restricts this service's ports to attached component IPs. MySQL X Protocol remains local-only. Other remote clients, including existing sites, may lose access. Enabling networking restarts the service. Stored data and local network access are kept, but enabling Redis / Valkey authentication may require local clients to use its password.</AlertDescription></Alert>
            <FormField>
              <Label htmlFor="resource-mode">Resource</Label>
              <Select value={form.data.mode} disabled={form.processing} onValueChange={(mode) => form.setData({ ...form.data, mode, service_id: '', server_id: '', database_id: '', charset: '', collation: '' })}>
                <SelectTrigger id="resource-mode"><SelectValue /></SelectTrigger>
                <SelectContent><SelectItem value="existing">Existing database</SelectItem><SelectItem value="new">New database</SelectItem><SelectItem value="redis">Redis / Valkey</SelectItem></SelectContent>
              </Select>
            </FormField>
            <FormField>
              <Label htmlFor="resource-service">Service</Label>
              <Select value={form.data.service_id} disabled={form.processing} onValueChange={selectService}>
                <SelectTrigger id="resource-service"><SelectValue placeholder="Select a service" /></SelectTrigger>
                <SelectContent>{services.map((item) => <SelectItem key={item.id} value={item.id.toString()}>{item.server_name} · {item.name}</SelectItem>)}</SelectContent>
              </Select>
              {services.length === 0 && <p className="text-muted-foreground text-sm">Install a ready service and firewall on a project server first.</p>}
            </FormField>
            {form.data.mode === 'existing' && <FormField>
              <Label htmlFor="resource-database">Database</Label>
              <Select value={form.data.database_id} disabled={form.processing || !service} onValueChange={(value) => form.setData('database_id', value)}>
                <SelectTrigger id="resource-database"><SelectValue placeholder="Select a database" /></SelectTrigger>
                <SelectContent>{service?.databases.map((database) => <SelectItem key={database.id} value={database.id.toString()}>{database.name}</SelectItem>)}</SelectContent>
              </Select>
            </FormField>}
            {form.data.mode === 'new' && <>
              <FormField><Label htmlFor="resource-name">Database name</Label><Input id="resource-name" required value={form.data.name} disabled={form.processing} onChange={(e) => form.setData('name', e.target.value)} /></FormField>
              <FormField><Label htmlFor="resource-charset">Charset</Label><Input id="resource-charset" required list="resource-charsets" value={form.data.charset} disabled={form.processing} onChange={(e) => form.setData({ ...form.data, charset: e.target.value, collation: service?.charsets[e.target.value]?.default ?? '' })} /><datalist id="resource-charsets">{Object.keys(service?.charsets ?? {}).map((charset) => <option key={charset} value={charset} />)}</datalist></FormField>
              <FormField><Label htmlFor="resource-collation">Collation</Label><Input id="resource-collation" required list="resource-collations" value={form.data.collation} disabled={form.processing} onChange={(e) => form.setData('collation', e.target.value)} /><datalist id="resource-collations">{service?.charsets[form.data.charset]?.list.map((collation) => <option key={collation} value={collation} />)}</datalist></FormField>
            </>}
            <FormField>
              <Label htmlFor="resource-alias">Alias (optional)</Label>
              <Input id="resource-alias" value={form.data.alias} maxLength={64} placeholder="ANALYTICS" disabled={form.processing} onChange={(e) => form.setData('alias', e.target.value)} />
              <p className="text-muted-foreground text-sm">Uppercase letters, digits and underscores, starting with a letter. Leave blank for the default resource of this kind.</p>
            </FormField>
            {!redis && <p className="text-muted-foreground text-sm">Vito creates separate managed credentials. Existing database users and passwords are never reused.</p>}
            {Object.entries(form.errors).map(([key, message]) => <InputError key={key} message={message} />)}
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild><Button variant="outline">Cancel</Button></DialogClose>
          <Button form="attach-resource-form" type="submit" disabled={form.processing || !service || (form.data.mode === 'existing' && !form.data.database_id)}>{form.processing && <LoaderCircleIcon className="animate-spin" />}Attach</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
