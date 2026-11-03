import { FormEvent } from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import { DialogControlProps } from '@/components/dialogs/registry';
import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { Application, Environment, EnvironmentDomain, EnvironmentDomainOptions } from '@/types/application';
import { SwitchField } from './section';

export default function DomainForm({
  open,
  onOpenChange,
  application,
  environment,
  options: initialOptions,
  domain,
  componentId,
}: DialogControlProps & {
  application: Application;
  environment: Environment;
  options: EnvironmentDomainOptions;
  domain?: EnvironmentDomain;
  componentId?: number;
}) {
  const page = usePage<{ application?: Application; environment?: Environment; domainOptions?: EnvironmentDomainOptions }>().props;
  const options =
    page.application?.id === application.id && page.environment?.id === environment.id ? (page.domainOptions ?? initialOptions) : initialOptions;
  const managedDomains = domain ? [] : options.dns_domains;
  const form = useForm({
    host: domain?.host ?? '',
    subdomain: '',
    dns_domain_id: managedDomains[0]?.id.toString() ?? '',
    type: domain?.type ?? 'alias',
    component_id: String(domain?.component_id ?? componentId ?? ''),
    redirect_to_domain_id: domain?.redirect_to_domain_id?.toString() ?? '',
    ssl_method: domain?.ssl_method === 'disabled' ? 'none' : (domain?.ssl_method ?? 'letsencrypt'),
    ssl_id: domain?.ssl_id?.toString() ?? '',
    force_ssl: domain?.force_ssl ?? true,
  });
  const redirect = form.data.type === 'redirect';
  const aliases = options.aliases.filter((item) => item.id !== domain?.id && (!componentId || item.component_id === componentId));
  const alias = options.aliases.find((item) => item.id.toString() === form.data.redirect_to_domain_id);
  const component = options.components.find((item) => item.id.toString() === (redirect ? alias?.component_id.toString() : form.data.component_id));
  const dnsDomain = managedDomains.find((item) => item.id.toString() === form.data.dns_domain_id);
  const typed = form.data.subdomain.trim().toLowerCase().replace(/\.+$/, '');
  const prefix =
    !dnsDomain || typed === dnsDomain.domain ? '' : typed.endsWith(`.${dnsDomain.domain}`) ? typed.slice(0, -dnsDomain.domain.length - 1) : typed;
  const host = (dnsDomain ? [prefix, dnsDomain.domain].filter(Boolean).join('.') : form.data.host.trim()).toLowerCase();
  const certificates = options.certificates.filter(
    (certificate) =>
      certificate.server_id === component?.server_id &&
      certificate.domains.some((name) => {
        const pattern = name.toLowerCase();
        return (
          pattern === host || (pattern.startsWith('*.') && host.endsWith(pattern.slice(1)) && host.split('.').length === pattern.split('.').length)
        );
      }),
  );
  const baseUrl = `/applications/${application.id}/environments/${environment.id}/domains`;
  const submit = (e: FormEvent) => {
    e.preventDefault();
    const requestOptions = { preserveScroll: true, preserveState: true, onSuccess: () => onOpenChange(false) };
    form.transform((data) => ({ ...data, host, dns_domain_id: dnsDomain?.id ?? null }));
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
          <DialogTitle>{domain ? `Edit ${domain.host}` : 'Add domain'}</DialogTitle>
          <DialogDescription>
            {dnsDomain
              ? 'Vito creates the DNS record and verifies the domain for you.'
              : 'Traffic starts once DNS points to the server and verification passes.'}
          </DialogDescription>
        </DialogHeader>
        <Form id="environment-domain-form" className="p-4" onSubmit={submit}>
          <FormFields>
            {managedDomains.length > 0 && (
              <FormField>
                <Label htmlFor="environment-domain-dns">Domain</Label>
                <Select
                  value={form.data.dns_domain_id || 'other'}
                  disabled={form.processing}
                  onValueChange={(value) => form.setData('dns_domain_id', value === 'other' ? '' : value)}
                >
                  <SelectTrigger id="environment-domain-dns">
                    <SelectValue />
                  </SelectTrigger>
                  <SelectContent>
                    {managedDomains.map((item) => (
                      <SelectItem key={item.id} value={item.id.toString()}>
                        {item.domain}
                      </SelectItem>
                    ))}
                    <SelectItem value="other">Another domain (manage DNS yourself)</SelectItem>
                  </SelectContent>
                </Select>
                <InputError message={form.errors.dns_domain_id} />
              </FormField>
            )}
            {dnsDomain ? (
              <FormField>
                <Label htmlFor="environment-domain-subdomain">Subdomain</Label>
                <Input
                  id="environment-domain-subdomain"
                  value={form.data.subdomain}
                  onChange={(e) => form.setData('subdomain', e.target.value)}
                  placeholder="app"
                  autoFocus
                  disabled={form.processing}
                />
                <p className="text-muted-foreground text-xs">
                  Creates a DNS record for <span className="text-foreground font-mono">{host}</span>
                  {component ? (
                    <>
                      {' '}
                      pointing to <span className="text-foreground font-mono">{component.server_ip}</span>
                    </>
                  ) : null}{' '}
                  on {dnsDomain.provider}. Leave empty to use {dnsDomain.domain}.
                </p>
                <InputError message={form.errors.host} />
              </FormField>
            ) : (
              <FormField>
                <Label htmlFor="environment-domain-host">{managedDomains.length > 0 ? 'Host' : 'Domain'}</Label>
                <Input
                  id="environment-domain-host"
                  value={form.data.host}
                  onChange={(e) => form.setData('host', e.target.value)}
                  placeholder="app.example.com"
                  autoFocus
                  disabled={form.processing}
                />
                {!domain && managedDomains.length === 0 && (
                  <p className="text-muted-foreground text-xs">
                    <Link href="/domains" className="underline">
                      Add your domain
                    </Link>{' '}
                    to let Vito create DNS records for you.
                  </p>
                )}
                <InputError message={form.errors.host} />
              </FormField>
            )}
            {aliases.length > 0 && (
              <FormField>
                <Label>Behavior</Label>
                <ToggleGroup
                  type="single"
                  variant="outline"
                  className="w-full"
                  value={form.data.type}
                  disabled={form.processing}
                  onValueChange={(value) =>
                    value && form.setData({ ...form.data, type: value as 'alias' | 'redirect', redirect_to_domain_id: '', ssl_id: '' })
                  }
                >
                  <ToggleGroupItem value="alias" className="flex-1">
                    Serve the app
                  </ToggleGroupItem>
                  <ToggleGroupItem value="redirect" className="flex-1">
                    Redirect
                  </ToggleGroupItem>
                </ToggleGroup>
                <InputError message={form.errors.type} />
              </FormField>
            )}
            {redirect ? (
              <FormField>
                <Label htmlFor="environment-domain-redirect">Redirect to</Label>
                <Select
                  value={form.data.redirect_to_domain_id}
                  disabled={form.processing}
                  onValueChange={(value) => form.setData({ ...form.data, redirect_to_domain_id: value, ssl_id: '' })}
                >
                  <SelectTrigger id="environment-domain-redirect">
                    <SelectValue placeholder="Select a domain" />
                  </SelectTrigger>
                  <SelectContent>
                    {aliases.map((item) => (
                      <SelectItem key={item.id} value={item.id.toString()}>
                        {item.host}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <InputError message={form.errors.redirect_to_domain_id} />
              </FormField>
            ) : (
              !componentId && (
                <FormField>
                  <Label htmlFor="environment-domain-component">Component</Label>
                  <Select
                    value={form.data.component_id}
                    disabled={form.processing}
                    onValueChange={(value) => form.setData({ ...form.data, component_id: value, ssl_id: '' })}
                  >
                    <SelectTrigger id="environment-domain-component">
                      <SelectValue placeholder="Select a web component" />
                    </SelectTrigger>
                    <SelectContent>
                      {options.components.map((item) => (
                        <SelectItem key={item.id} value={item.id.toString()}>
                          {item.name} on {item.server_name}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </FormField>
              )
            )}
            <InputError message={form.errors.component_id} />
            <FormField>
              <Label htmlFor="environment-domain-ssl">SSL certificate</Label>
              <Select
                value={form.data.ssl_method}
                disabled={form.processing}
                onValueChange={(value) =>
                  form.setData({ ...form.data, ssl_method: value, ssl_id: '', force_ssl: value !== 'none' && form.data.force_ssl })
                }
              >
                <SelectTrigger id="environment-domain-ssl">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="letsencrypt">Let's Encrypt (free, automatic)</SelectItem>
                  <SelectItem value="custom">Custom certificate</SelectItem>
                  <SelectItem value="none">None</SelectItem>
                </SelectContent>
              </Select>
              <InputError message={form.errors.ssl_method} />
            </FormField>
            {form.data.ssl_method === 'custom' && (
              <FormField>
                <Label htmlFor="environment-domain-certificate">Certificate</Label>
                <Select value={form.data.ssl_id} disabled={form.processing} onValueChange={(value) => form.setData('ssl_id', value)}>
                  <SelectTrigger id="environment-domain-certificate">
                    <SelectValue placeholder={certificates.length ? 'Select a certificate' : 'No matching certificates'} />
                  </SelectTrigger>
                  <SelectContent>
                    {certificates.map((certificate) => (
                      <SelectItem key={certificate.id} value={certificate.id.toString()}>
                        {certificate.domains.join(', ')}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <p className="text-muted-foreground text-xs">
                  Upload certificates in the server's SSL page. Only valid ones covering this domain are listed.
                </p>
                <InputError message={form.errors.ssl_id} />
              </FormField>
            )}
            {form.data.ssl_method !== 'none' && (
              <SwitchField
                id="environment-domain-force-ssl"
                label="Redirect HTTP to HTTPS"
                description="Visitors on http:// are sent to https://."
                checked={form.data.force_ssl}
                disabled={form.processing}
                onCheckedChange={(checked) => form.setData('force_ssl', checked)}
                error={form.errors.force_ssl}
              />
            )}
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button form="environment-domain-form" type="submit" disabled={form.processing || !host}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            {domain ? 'Save' : 'Add domain'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
