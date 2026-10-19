import { FormEvent } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import { DialogControlProps } from '@/components/dialogs/registry';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Application, Environment, EnvironmentDomain, EnvironmentDomainOptions } from '@/types/application';

export default function DomainForm({ open, onOpenChange, application, environment, options: initialOptions, domain }: DialogControlProps & {
  application: Application;
  environment: Environment;
  options: EnvironmentDomainOptions;
  domain?: EnvironmentDomain;
}) {
  const page = usePage<{ application?: Application; environment?: Environment; domainOptions?: EnvironmentDomainOptions }>().props;
  const options = page.application?.id === application.id && page.environment?.id === environment.id ? page.domainOptions ?? initialOptions : initialOptions;
  const form = useForm({
    host: domain?.host ?? '',
    type: domain?.type ?? 'alias',
    component_id: domain?.component_id.toString() ?? '',
    redirect_to_domain_id: domain?.redirect_to_domain_id?.toString() ?? '',
    ssl_method: domain?.ssl_method === 'disabled' ? 'none' : domain?.ssl_method ?? 'letsencrypt',
    ssl_id: domain?.ssl_id?.toString() ?? '',
    force_ssl: domain?.force_ssl ?? false,
  });
  const redirect = form.data.type === 'redirect';
  const alias = options.aliases.find((item) => item.id.toString() === form.data.redirect_to_domain_id);
  const component = options.components.find((item) => item.id.toString() === (redirect ? alias?.component_id.toString() : form.data.component_id));
  const host = form.data.host.trim().toLowerCase();
  const certificates = options.certificates.filter((certificate) => certificate.server_id === component?.server_id && certificate.domains.some((name) => {
    const pattern = name.toLowerCase();
    return pattern === host || (pattern.startsWith('*.') && host.endsWith(pattern.slice(1)) && host.split('.').length === pattern.split('.').length);
  }));
  const baseUrl = `/applications/${application.id}/environments/${environment.id}/domains`;
  const submit = (e: FormEvent) => {
    e.preventDefault();
    const requestOptions = { onSuccess: () => onOpenChange(false) };
    if (domain) {
      form.put(`${baseUrl}/${domain.id}`, requestOptions);
    } else {
      form.post(baseUrl, requestOptions);
    }
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>{domain ? 'Edit domain' : 'Add domain'}</DialogTitle>
          <DialogDescription>Domains serve traffic only after DNS and HTTP verification on the target server. Wildcards need a project domain with a DNS provider.</DialogDescription>
        </DialogHeader>
        <Form id="environment-domain-form" className="p-4" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="environment-domain-host">Host</Label>
              <Input id="environment-domain-host" value={form.data.host} onChange={(e) => form.setData('host', e.target.value)} placeholder="app.example.com" disabled={form.processing} />
              <InputError message={form.errors.host} />
            </FormField>
            <FormField>
              <Label htmlFor="environment-domain-type">Type</Label>
              <Select value={form.data.type} disabled={form.processing} onValueChange={(value: 'alias' | 'redirect') => form.setData({ ...form.data, type: value, redirect_to_domain_id: '', ssl_id: '' })}>
                <SelectTrigger id="environment-domain-type"><SelectValue /></SelectTrigger>
                <SelectContent><SelectItem value="alias">Alias</SelectItem><SelectItem value="redirect">Redirect</SelectItem></SelectContent>
              </Select>
              <InputError message={form.errors.type} />
            </FormField>
            {redirect ? <FormField>
              <Label htmlFor="environment-domain-redirect">Redirect to</Label>
              <Select value={form.data.redirect_to_domain_id} disabled={form.processing} onValueChange={(value) => form.setData({ ...form.data, redirect_to_domain_id: value, ssl_id: '' })}>
                <SelectTrigger id="environment-domain-redirect"><SelectValue placeholder="Select an alias" /></SelectTrigger>
                <SelectContent>{options.aliases.filter((item) => item.id !== domain?.id).map((item) => <SelectItem key={item.id} value={item.id.toString()}>{item.host}</SelectItem>)}</SelectContent>
              </Select>
              <InputError message={form.errors.redirect_to_domain_id} />
            </FormField> : <FormField>
              <Label htmlFor="environment-domain-component">Target component</Label>
              <Select value={form.data.component_id} disabled={form.processing} onValueChange={(value) => form.setData({ ...form.data, component_id: value, ssl_id: '' })}>
                <SelectTrigger id="environment-domain-component"><SelectValue placeholder="Select a web component" /></SelectTrigger>
                <SelectContent>{options.components.map((item) => <SelectItem key={item.id} value={item.id.toString()}>{item.name} · {item.server_name}</SelectItem>)}</SelectContent>
              </Select>
            </FormField>}
            <InputError message={form.errors.component_id} />
            {component && <p className="text-muted-foreground text-sm">Point this host to {component.server_ip} ({component.server_name}).{redirect && ` The redirect uses ${component.name}.`}</p>}
            <FormField>
              <Label htmlFor="environment-domain-ssl">SSL method</Label>
              <Select value={form.data.ssl_method} disabled={form.processing} onValueChange={(value) => form.setData({ ...form.data, ssl_method: value, ssl_id: '' })}>
                <SelectTrigger id="environment-domain-ssl"><SelectValue /></SelectTrigger>
                <SelectContent><SelectItem value="none">None</SelectItem><SelectItem value="letsencrypt">Let's Encrypt</SelectItem><SelectItem value="custom">Custom certificate</SelectItem></SelectContent>
              </Select>
              <InputError message={form.errors.ssl_method} />
            </FormField>
            {form.data.ssl_method === 'custom' && <FormField>
              <Label htmlFor="environment-domain-certificate">Certificate</Label>
              <Select value={form.data.ssl_id} disabled={form.processing} onValueChange={(value) => form.setData('ssl_id', value)}>
                <SelectTrigger id="environment-domain-certificate"><SelectValue placeholder="Select a server certificate" /></SelectTrigger>
                <SelectContent>{certificates.map((certificate) => <SelectItem key={certificate.id} value={certificate.id.toString()}>#{certificate.id} · {certificate.domains.join(', ')}</SelectItem>)}</SelectContent>
              </Select>
              <p className="text-muted-foreground text-sm">Only unexpired certificates covering this host are shown. Upload custom certificates in the target server's SSL settings first.</p>
            </FormField>}
            <InputError message={form.errors.ssl_id} />
            <FormField>
              <div className="flex items-center gap-2">
                <Checkbox id="environment-domain-force-ssl" checked={form.data.force_ssl} disabled={form.processing} onCheckedChange={(checked) => form.setData('force_ssl', checked === true)} />
                <Label htmlFor="environment-domain-force-ssl">Force HTTPS when a certificate is active</Label>
              </div>
              <InputError message={form.errors.force_ssl} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild><Button variant="outline">Cancel</Button></DialogClose>
          <Button form="environment-domain-form" type="submit" disabled={form.processing}>{form.processing && <LoaderCircleIcon className="animate-spin" />}Save</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
