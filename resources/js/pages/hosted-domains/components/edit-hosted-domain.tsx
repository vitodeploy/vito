import React, { FormEvent, ReactNode, useCallback, useEffect, useRef, useState } from 'react';
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
import { AvailableSsl, HostedDomain } from '@/types/hosted-domain';
import FormSuccessful from '@/components/form-successful';
import axios from 'axios';

type EditForm = {
  domain: string;
  type: string;
  ssl_mode: string;
  ssl_id: string;
};

export default function EditHostedDomain({
  hostedDomain,
  children,
}: {
  hostedDomain: HostedDomain;
  children: ReactNode;
}) {
  const [open, setOpen] = useState(false);
  const [matchingSsls, setMatchingSsls] = useState<AvailableSsl[]>([]);
  const [loadingSsls, setLoadingSsls] = useState(false);
  const lastFetchedDomain = useRef(hostedDomain.domain);
  const isPrimary = hostedDomain.type === 'primary';

  const form = useForm<EditForm>({
    domain: hostedDomain.domain,
    type: hostedDomain.type,
    ssl_mode: hostedDomain.ssl_method,
    ssl_id: hostedDomain.ssl_id ? String(hostedDomain.ssl_id) : '',
  });

  const sslStale = form.data.domain !== lastFetchedDomain.current;

  const fetchMatchingSsls = useCallback(
    (domain: string) => {
      if (!domain) {
        setMatchingSsls([]);
        lastFetchedDomain.current = domain;
        return;
      }

      setLoadingSsls(true);
      axios
        .get(route('hosted-domains.matching-ssls', { server: hostedDomain.server_id, site: hostedDomain.site_id, domain }))
        .then((response) => {
          const { certificates, best_match_id } = response.data;
          setMatchingSsls(certificates);
          lastFetchedDomain.current = domain;
          if (domain !== hostedDomain.domain) {
            if (best_match_id) {
              form.setData('ssl_mode', 'custom');
              form.setData('ssl_id', String(best_match_id));
            } else {
              form.setData('ssl_mode', 'letsencrypt');
              form.setData('ssl_id', '');
            }
          }
        })
        .finally(() => {
          setLoadingSsls(false);
        });
    },
    [hostedDomain.server_id, hostedDomain.site_id, hostedDomain.domain],
  );

  useEffect(() => {
    if (!open) {
      return;
    }

    if (sslStale && form.data.ssl_mode === 'custom') {
      form.setData('ssl_mode', 'letsencrypt');
      form.setData('ssl_id', '');
    }

    fetchMatchingSsls(form.data.domain);
  }, [open, form.data.domain, fetchMatchingSsls]);

  const handleSslModeChange = (value: string) => {
    form.setData('ssl_mode', value);
    if (value !== 'custom') {
      form.setData('ssl_id', '');
    }
  };

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
          setMatchingSsls([]);
          lastFetchedDomain.current = hostedDomain.domain;
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
              <Label htmlFor="edit-ssl-mode">SSL</Label>
              <Select onValueChange={handleSslModeChange} value={form.data.ssl_mode}>
                <SelectTrigger id="edit-ssl-mode">
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="none">Disabled</SelectItem>
                  <SelectItem value="letsencrypt">Generate Let&apos;s Encrypt Certificate</SelectItem>
                  <SelectItem value="custom">Custom Certificate</SelectItem>
                </SelectContent>
              </Select>
              <InputError message={form.errors.ssl_mode} />
            </FormField>
            {form.data.ssl_mode === 'custom' && (
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
                  Only server-level SSL certificates that match the domain you entered will appear here. Add certificates via the server SSL
                  settings.
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
