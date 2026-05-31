import { FormEvent } from 'react';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Site } from '@/types/site';
import FormSuccessful from '@/components/form-successful';
import { useSslMatching } from '@/pages/hosted-domains/hooks/use-ssl-matching';

type CreateForm = {
  domain: string;
  type: string;
  ssl_method: string;
  ssl_id: string;
};

const SSL_METHOD_OPTIONS: { value: string; label: string }[] = [
  { value: 'none', label: 'Disabled' },
  { value: 'letsencrypt', label: "Generate Let's Encrypt Certificate" },
  { value: 'custom', label: 'Custom Certificate' },
];

export default function CreateHostedDomain({ open, onOpenChange, site }: { open: boolean; onOpenChange: (open: boolean) => void; site: Site }) {
  const { ssl_enabled } = site;
  const allowedSslMethods = site.webserver_allowed_ssl_methods;
  const sslMethodOptions = allowedSslMethods ? SSL_METHOD_OPTIONS.filter((option) => allowedSslMethods.includes(option.value)) : SSL_METHOD_OPTIONS;
  const defaultSslMethod = !ssl_enabled && sslMethodOptions.some((option) => option.value === 'none') ? 'none' : site.webserver_default_ssl_method;
  const form = useForm<CreateForm>({
    domain: '',
    type: 'alias',
    ssl_method: defaultSslMethod,
    ssl_id: '',
  });

  const { matchingSsls, loadingSsls, sslStale, handleSslMethodChange } = useSslMatching({
    serverId: site.server_id,
    siteId: site.id,
    domain: form.data.domain,
    sslId: form.data.ssl_id,
    applySslSettings: ({ ssl_method, ssl_id }) => {
      form.setData('ssl_method', ssl_method);
      form.setData('ssl_id', ssl_id);
    },
    open,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(
      route('hosted-domains.store', {
        server: site.server_id,
        site: site.id,
      }),
      {
        onSuccess: () => onOpenChange(false),
      },
    );
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Add Domain</DialogTitle>
          <DialogDescription className="sr-only">Add a new domain</DialogDescription>
        </DialogHeader>
        <Form className="p-4" id="create-hosted-domain-form" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="domain">Domain</Label>
              <Input
                type="text"
                id="domain"
                name="domain"
                value={form.data.domain}
                onChange={(e) => form.setData('domain', e.target.value)}
                placeholder="example.com"
              />
              <InputError message={form.errors.domain} />
            </FormField>
            <FormField>
              <Label htmlFor="type">Type</Label>
              <Select onValueChange={(value) => form.setData('type', value)} value={form.data.type}>
                <SelectTrigger id="type">
                  <SelectValue placeholder="Select type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="alias">Alias</SelectItem>
                  <SelectItem value="redirect">Redirect</SelectItem>
                </SelectContent>
              </Select>
              <InputError message={form.errors.type} />
            </FormField>
            <FormField>
              <Label htmlFor="ssl-method">SSL</Label>
              <Select onValueChange={handleSslMethodChange} value={form.data.ssl_method}>
                <SelectTrigger id="ssl-method">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {sslMethodOptions.map((option) => (
                    <SelectItem key={option.value} value={option.value}>
                      {option.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <InputError message={form.errors.ssl_method} />
            </FormField>
            {form.data.ssl_method === 'custom' && (
              <FormField>
                <Label htmlFor="ssl_id">SSL Certificate</Label>
                <Select onValueChange={(value) => form.setData('ssl_id', value)} value={form.data.ssl_id}>
                  <SelectTrigger id="ssl_id">
                    <SelectValue placeholder={loadingSsls ? 'Loading...' : 'Select a certificate'} />
                  </SelectTrigger>
                  <SelectContent>
                    {matchingSsls.map((ssl) => (
                      <SelectItem key={ssl.id} value={String(ssl.id)}>
                        {ssl.label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <p className="text-muted-foreground text-sm">
                  Only server-level SSL certificates that match the domain you entered will appear here. Add certificates via the server SSL settings.
                </p>
                <InputError message={form.errors.ssl_id} />
              </FormField>
            )}
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button type="button" variant="outline">
              Cancel
            </Button>
          </DialogClose>
          <Button type="button" onClick={submit} disabled={form.processing || loadingSsls || sslStale}>
            {(form.processing || loadingSsls || sslStale) && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Add Domain
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
