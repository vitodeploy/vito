import { LoaderCircle, GlobeIcon, RefreshCw, WifiIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
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
import { useForm } from '@inertiajs/react';
import { FormEventHandler, ReactNode, useEffect, useState } from 'react';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/ui/input-error';
import { Form, FormField, FormFields } from '@/components/ui/form';
import ConnectDNSProvider from '@/pages/dns-providers/components/connect-dns-provider';
import axios from 'axios';
import { DNSProvider } from '@/types/dns-provider';

type Domain = {
  id: string;
  name: string;
  status: string;
};

export default function AddDomain({ children }: { children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const [availableDomains, setAvailableDomains] = useState<Domain[]>([]);
  const [loadingDomains, setLoadingDomains] = useState(false);
  const [dnsProviders, setDNSProviders] = useState<DNSProvider[]>([]);

  const form = useForm({
    dns_provider_id: '',
    provider_domain_id: '',
  });

  const connectedProviders = dnsProviders.filter((provider) => provider.connected);

  const fetchDomains = async (providerId: string, routeName: string = 'domains.available') => {
    if (!providerId) {
      setAvailableDomains([]);
      return;
    }

    setLoadingDomains(true);
    try {
      const response = await axios.get(route(routeName, providerId));
      setAvailableDomains(response.data || []);
    } catch (error) {
      console.error('Failed to fetch domains:', error);
      setAvailableDomains([]);
    } finally {
      setLoadingDomains(false);
    }
  };

  const selectProvider = async (providerId: string) => {
    form.setData('dns_provider_id', providerId);
    form.setData('provider_domain_id', '');
    form.clearErrors();
    fetchDomains(providerId);
  };

  const refreshDomains = () => {
    form.setData('provider_domain_id', '');
    fetchDomains(form.data.dns_provider_id, 'domains.refresh');
  };

  const fetchProviders = async () => {
    const response = await axios.get(route('dns-providers.json'));
    setDNSProviders(response.data as DNSProvider[]);
  };

  const submit: FormEventHandler = (e) => {
    e.preventDefault();
    form.post(route('domains.store'), {
      onSuccess: () => {
        setOpen(false);
        setAvailableDomains([]);
        form.reset();
      },
    });
  };

  useEffect(() => {
    if (open) {
      form.setData({ dns_provider_id: '', provider_domain_id: '' });
      form.clearErrors();
      setAvailableDomains([]);
      setLoadingDomains(false);
      fetchProviders();
    }
  }, [open]);

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="max-h-screen overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Add Domain</DialogTitle>
          <DialogDescription className="sr-only">Add a domain from your DNS provider</DialogDescription>
        </DialogHeader>
        <Form id="add-domain-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="dns_provider_id">DNS Provider</Label>
              <div className="flex items-center gap-2">
                <Select value={form.data.dns_provider_id} onValueChange={selectProvider}>
                  <SelectTrigger id="dns_provider_id">
                    <SelectValue placeholder="Select a DNS provider" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      {connectedProviders.map((provider) => (
                        <SelectItem key={provider.id} value={provider.id.toString()}>
                          {provider.name} ({provider.provider})
                        </SelectItem>
                      ))}
                    </SelectGroup>
                  </SelectContent>
                </Select>
                <ConnectDNSProvider onProviderAdded={fetchProviders}>
                  <Button variant="outline" aria-label="Connect DNS Provider">
                    <WifiIcon />
                  </Button>
                </ConnectDNSProvider>
              </div>
              <InputError message={form.errors.dns_provider_id} />
            </FormField>
            <FormField>
              <Label htmlFor="provider_domain_id">Domain</Label>
              <div className="flex items-center gap-2">
                <Select
                  value={form.data.provider_domain_id}
                  onValueChange={(value) => form.setData('provider_domain_id', value)}
                  disabled={!form.data.dns_provider_id || loadingDomains}
                >
                  <SelectTrigger id="provider_domain_id">
                    <SelectValue placeholder={loadingDomains ? 'Loading domains...' : 'Select a domain'} />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      {availableDomains.map((domain) => (
                        <SelectItem key={domain.id} value={domain.id}>
                          <div className="flex items-center gap-2">
                            <GlobeIcon className="h-4 w-4" />
                            {domain.name}
                            <span className="text-muted-foreground text-xs">({domain.status})</span>
                          </div>
                        </SelectItem>
                      ))}
                    </SelectGroup>
                  </SelectContent>
                </Select>
                <Button variant="outline" type="button" disabled={loadingDomains || !form.data.dns_provider_id} onClick={refreshDomains}>
                  <RefreshCw className={loadingDomains ? 'animate-spin' : ''} />
                </Button>
              </div>
              <InputError message={form.errors.provider_domain_id} />
            </FormField>
            <InputError message={(form.errors as Record<string, string>).domain} />
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button type="button" variant="outline">
              Cancel
            </Button>
          </DialogClose>
          <Button type="button" onClick={submit} disabled={form.processing || !form.data.dns_provider_id || !form.data.provider_domain_id}>
            {form.processing && <LoaderCircle className="animate-spin" />}
            Add Domain
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
