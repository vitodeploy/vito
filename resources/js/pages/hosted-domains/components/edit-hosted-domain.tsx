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
import { useForm, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { HostedDomain } from '@/types/hosted-domain';
import { Site } from '@/types/site';
import FormSuccessful from '@/components/form-successful';
import { useSslMatching } from '@/pages/hosted-domains/hooks/use-ssl-matching';

type EditForm = {
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

export default function EditHostedDomain({ hostedDomain, children }: { hostedDomain: HostedDomain; children: ReactNode }) {
  const { site } = usePage<{ site: Site }>().props;
  const [open, setOpen] = useState(false);
  const isPrimary = hostedDomain.type === 'primary';

  const allowedMethods = site.webserver_allowed_ssl_methods;
  const filteredSslOptions = allowedMethods ? SSL_METHOD_OPTIONS.filter((o) => allowedMethods.includes(o.value)) : SSL_METHOD_OPTIONS;

  const form = useForm<EditForm>({
    domain: hostedDomain.domain,
    type: hostedDomain.type,
    ssl_method: hostedDomain.ssl_method,
    ssl_id: hostedDomain.ssl_id ? String(hostedDomain.ssl_id) : '',
  });

  const { matchingSsls, loadingSsls, sslStale, handleSslMethodChange, reset } = useSslMatching({
    serverId: hostedDomain.server_id,
    siteId: hostedDomain.site_id,
    domain: form.data.domain,
    sslMethod: form.data.ssl_method,
    setData: form.setData,
    open,
    originalDomain: hostedDomain.domain,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.put(
      route('hosted-domains.update', {
        server: hostedDomain.server_id,
        site: hostedDomain.site_id,
        hostedDomain: hostedDomain.id,
      }),
      {
        onSuccess: () => {
          setOpen(false);
        },
      },
    );
  };

  return (
    <Dialog
      open={open}
      onOpenChange={(value) => {
        setOpen(value);
        if (!value) {
          form.setData({
            domain: hostedDomain.domain,
            type: hostedDomain.type,
            ssl_method: hostedDomain.ssl_method,
            ssl_id: hostedDomain.ssl_id ? String(hostedDomain.ssl_id) : '',
          });
          form.clearErrors();
          reset(hostedDomain.domain);
        }
      }}
    >
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Edit Domain</DialogTitle>
          <DialogDescription className="sr-only">Edit hosted domain</DialogDescription>
        </DialogHeader>
        <Form className="p-4" id="edit-hosted-domain-form" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="edit-domain">Domain</Label>
              <Input
                type="text"
                id="edit-domain"
                name="domain"
                value={form.data.domain}
                onChange={(e) => form.setData('domain', e.target.value)}
                placeholder="example.com"
                disabled={isPrimary}
              />
              <InputError message={form.errors.domain} />
            </FormField>
            <FormField>
              <Label htmlFor="edit-type">Type</Label>
              <Select onValueChange={(value) => form.setData('type', value)} value={form.data.type} disabled={isPrimary}>
                <SelectTrigger id="edit-type">
                  <SelectValue placeholder="Select type" />
                </SelectTrigger>
                <SelectContent>
                  {isPrimary && <SelectItem value="primary">Primary</SelectItem>}
                  <SelectItem value="alias">Alias</SelectItem>
                  <SelectItem value="redirect">Redirect</SelectItem>
                </SelectContent>
              </Select>
              <InputError message={form.errors.type} />
            </FormField>
            <FormField>
              <Label htmlFor="edit-ssl-method">SSL</Label>
              <Select onValueChange={handleSslMethodChange} value={form.data.ssl_method}>
                <SelectTrigger id="edit-ssl-method">
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
                <Label htmlFor="edit-ssl_id">SSL Certificate</Label>
                <Select onValueChange={(value) => form.setData('ssl_id', value)} value={form.data.ssl_id}>
                  <SelectTrigger id="edit-ssl_id">
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
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
