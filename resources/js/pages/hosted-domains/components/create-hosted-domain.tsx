import React, { FormEvent, ReactNode, useState } from 'react';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Site } from '@/types/site';
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

export default function CreateHostedDomain({ site, children }: { site: Site; children: ReactNode }) {
  const [open, setOpen] = useState(false);

  const allowedMethods = site.webserver_allowed_ssl_methods;
  const filteredSslOptions = allowedMethods ? SSL_METHOD_OPTIONS.filter((o) => allowedMethods.includes(o.value)) : SSL_METHOD_OPTIONS;
  const defaultSslMethod = site.webserver_default_ssl_method ?? 'letsencrypt';

  const form = useForm<CreateForm>({
    domain: '',
    type: 'alias',
    ssl_method: defaultSslMethod,
    ssl_id: '',
  });

  const { matchingSsls, loadingSsls, sslStale, handleSslMethodChange, reset } = useSslMatching({
    serverId: site.server_id,
    siteId: site.id,
    domain: form.data.domain,
    sslMethod: form.data.ssl_method,
    setData: form.setData,
    open,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('hosted-domains.store', { server: site.server_id, site: site.id }), {
      onSuccess: () => {
        form.reset();
        reset();
        setOpen(false);
      },
    });
  };

  return (
    <Dialog
      open={open}
      onOpenChange={(value) => {
        setOpen(value);
        if (!value) {
          form.reset();
          reset();
        }
      }}
    >
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Add Domain</DialogTitle>
          <DialogDescription className="sr-only">Add a new hosted domain</DialogDescription>
        </DialogHeader>
        <Form className="p-4" id="create-hosted-domain-form" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="create-domain">Domain</Label>
              <Input
                type="text"
                id="create-domain"
                name="domain"
                value={form.data.domain}
                onChange={(e) => form.setData('domain', e.target.value)}
                placeholder="sub.example.com"
              />
              <InputError message={form.errors.domain} />
            </FormField>
            <FormField>
              <Label htmlFor="create-type">Type</Label>
              <Select onValueChange={(value) => form.setData('type', value)} value={form.data.type}>
                <SelectTrigger id="create-type">
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
              <Label htmlFor="create-ssl-method">SSL</Label>
              <Select onValueChange={handleSslMethodChange} value={form.data.ssl_method}>
                <SelectTrigger id="create-ssl-method">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  {filteredSslOptions.map((option) => (
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
                <Label htmlFor="create-ssl_id">SSL Certificate</Label>
                <Select onValueChange={(value) => form.setData('ssl_id', value)} value={form.data.ssl_id}>
                  <SelectTrigger id="create-ssl_id">
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
            Add
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
