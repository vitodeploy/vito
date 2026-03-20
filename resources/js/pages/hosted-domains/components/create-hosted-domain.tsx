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
import { Site } from '@/types/site';
import { AvailableSsl } from '@/types/hosted-domain';
import axios from 'axios';

type CreateForm = {
  domain: string;
  type: string;
  ssl_mode: string;
  ssl_id: string;
};

export default function CreateHostedDomain({ site, children }: { site: Site; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const [matchingSsls, setMatchingSsls] = useState<AvailableSsl[]>([]);
  const [loadingSsls, setLoadingSsls] = useState(false);
  const lastFetchedDomain = useRef('');

  const form = useForm<CreateForm>({
    domain: '',
    type: 'alias',
    ssl_mode: 'letsencrypt',
    ssl_id: '',
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
        .get(route('hosted-domains.matching-ssls', { server: site.server_id, site: site.id, domain }))
        .then((response) => {
          const { certificates, best_match_id } = response.data;
          setMatchingSsls(certificates);
          lastFetchedDomain.current = domain;
          if (best_match_id) {
            form.setData('ssl_mode', 'custom');
            form.setData('ssl_id', String(best_match_id));
          } else {
            form.setData('ssl_mode', 'letsencrypt');
            form.setData('ssl_id', '');
          }
        })
        .finally(() => {
          setLoadingSsls(false);
        });
    },
    [site.server_id, site.id],
  );

  useEffect(() => {
    if (!open) {
      return;
    }

    if (sslStale && form.data.ssl_mode === 'custom') {
      form.setData('ssl_mode', 'letsencrypt');
      form.setData('ssl_id', '');
    }

    const timeoutId = setTimeout(() => {
      fetchMatchingSsls(form.data.domain);
    }, 500);

    return () => clearTimeout(timeoutId);
  }, [form.data.domain, open, fetchMatchingSsls]);

  const handleSslModeChange = (value: string) => {
    form.setData('ssl_mode', value);
    if (value !== 'custom') {
      form.setData('ssl_id', '');
    }
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('hosted-domains.store', { server: site.server_id, site: site.id }), {
      onSuccess: () => {
        form.reset();
        setMatchingSsls([]);
        lastFetchedDomain.current = '';
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
          setMatchingSsls([]);
          lastFetchedDomain.current = '';
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
              <Label htmlFor="create-ssl-mode">SSL</Label>
              <Select onValueChange={handleSslModeChange} value={form.data.ssl_mode}>
                <SelectTrigger id="create-ssl-mode">
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
            Add
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
