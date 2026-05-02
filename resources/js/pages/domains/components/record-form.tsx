import { LoaderCircle } from 'lucide-react';
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
import { useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, ReactNode, useState, useEffect } from 'react';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/ui/input-error';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Checkbox } from '@/components/ui/checkbox';
import { Domain } from '@/types/domain';
import { DNSRecord } from '@/types/dns-record';
import { SharedData } from '@/types';
import FormSuccessful from '@/components/form-successful';

const PREDEFINED_TTLS = [1, 300, 600, 1800, 3600, 7200, 14400, 28800, 43200, 86400];

interface RecordFormProps {
  domain: Domain;
  record?: DNSRecord;
  defaultOpen?: boolean;
  onOpenChange?: (open: boolean) => void;
  children: ReactNode;
}

export default function RecordForm({ domain, record, defaultOpen, onOpenChange, children }: RecordFormProps) {
  const [open, setOpen] = useState(defaultOpen || false);
  const pageProps = usePage<SharedData & { domain: Domain }>().props;
  const { configs } = pageProps;

  const providerKey = pageProps.domain?.dns_provider?.provider ?? domain.dns_provider?.provider;
  const providerConfig = providerKey ? configs.dns_provider?.providers?.[providerKey] : undefined;
  const proxyTypes = providerConfig?.proxy_types ?? [];

  const isManualTtl = record ? !PREDEFINED_TTLS.includes(record.ttl) : false;
  const [manualTtl, setManualTtl] = useState(isManualTtl);

  useEffect(() => {
    if (defaultOpen) {
      setOpen(defaultOpen);
    }
  }, [setOpen, defaultOpen]);

  const handleOpenChange = (open: boolean) => {
    setOpen(open);
    if (onOpenChange) {
      onOpenChange(open);
    }
  };

  const form = useForm({
    type: record?.type || 'A',
    name: record?.name || '',
    content: record?.content || '',
    ttl: record?.ttl || 1,
    proxied: record?.proxied || false,
    priority: record?.priority ?? ((record?.type === 'MX' ? 10 : null) as number | null),
  });

  useEffect(() => {
    const updates: Partial<typeof form.data> = {};

    if (!proxyTypes.includes(form.data.type)) {
      updates.proxied = false;
    }

    if (form.data.type === 'MX' && form.data.priority == null) {
      updates.priority = 10;
    } else if (form.data.type !== 'MX') {
      updates.priority = null;
    }

    if (Object.keys(updates).length > 0) {
      form.setData((prev) => ({ ...prev, ...updates }));
    }
  }, [form.data.type, proxyTypes]);

  const submit: FormEventHandler = (e) => {
    e.preventDefault();

    if (record) {
      form.patch(route('dns-records.update', [record.domain_id, record.id]), {
        onSuccess: () => {
          setOpen(false);
        },
      });
    } else {
      form.post(route('dns-records.store', domain.id), {
        onSuccess: () => {
          setOpen(false);
          form.reset();
        },
      });
    }
  };

  const recordTypes = [
    { value: 'A', label: 'A' },
    { value: 'AAAA', label: 'AAAA' },
    { value: 'CNAME', label: 'CNAME' },
    { value: 'TXT', label: 'TXT' },
    { value: 'MX', label: 'MX' },
    { value: 'NS', label: 'NS' },
    { value: 'SRV', label: 'SRV' },
    { value: 'PTR', label: 'PTR' },
    { value: 'CAA', label: 'CAA' },
    { value: 'SOA', label: 'SOA' },
  ];

  const getContentPlaceholder = () => {
    switch (form.data.type) {
      case 'A':
        return '192.168.1.1';
      case 'AAAA':
        return '2001:db8::1';
      case 'CNAME':
        return 'example.com';
      default:
        return 'your text value';
    }
  };

  const getContentDescription = () => {
    switch (form.data.type) {
      case 'A':
        return 'Enter an IPv4 address (e.g., 192.168.1.1)';
      case 'AAAA':
        return 'Enter an IPv6 address (e.g., 2001:db8::1)';
      case 'CNAME':
        return 'Enter a domain name (e.g., example.com)';
      case 'TXT':
        return 'Enter text content (e.g., "v=spf1 include:_spf.google.com ~all")';
      default:
        return '';
    }
  };

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="max-h-screen overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>{record ? 'Edit DNS Record' : 'Create DNS Record'}</DialogTitle>
          <DialogDescription className="sr-only">{record ? 'Edit DNS record' : `Create a new DNS record for ${domain.domain}`}</DialogDescription>
        </DialogHeader>
        <Form id={record ? 'edit-record-form' : 'create-record-form'} onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="type">Type</Label>
              <Select value={form.data.type} onValueChange={(value) => form.setData('type', value)}>
                <SelectTrigger id="type">
                  <SelectValue placeholder="Select record type" />
                </SelectTrigger>
                <SelectContent>
                  {recordTypes.map((type) => (
                    <SelectItem key={type.value} value={type.value}>
                      {type.label}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              <InputError message={form.errors.type} />
            </FormField>
            <FormField>
              <Label htmlFor="name">Name</Label>
              <Input
                type="text"
                id="name"
                name="name"
                value={form.data.name}
                onChange={(e) => form.setData('name', e.target.value)}
                placeholder="subdomain or @ for root"
              />
              <div className="text-muted-foreground text-xs">Use @ for the root domain or enter a subdomain (e.g., www, api)</div>
              <InputError message={form.errors.name} />
            </FormField>
            <FormField>
              <Label htmlFor="content">Content</Label>
              {form.data.type === 'TXT' ? (
                <Textarea
                  id="content"
                  name="content"
                  value={form.data.content}
                  onChange={(e) => form.setData('content', e.target.value)}
                  placeholder={getContentPlaceholder()}
                />
              ) : (
                <Input
                  type="text"
                  id="content"
                  name="content"
                  value={form.data.content}
                  onChange={(e) => form.setData('content', e.target.value)}
                  placeholder={getContentPlaceholder()}
                />
              )}
              <div className="text-muted-foreground text-xs">{getContentDescription()}</div>
              <InputError message={form.errors.content} />
            </FormField>
            {form.data.type === 'MX' && (
              <FormField>
                <Label htmlFor="priority">Priority</Label>
                <Input
                  type="number"
                  id="priority"
                  name="priority"
                  value={form.data.priority ?? ''}
                  onChange={(e) => form.setData('priority', e.target.value === '' ? null : parseInt(e.target.value))}
                  placeholder="10"
                  min={0}
                  max={65535}
                />
                <div className="text-muted-foreground text-xs">Lower values have higher priority (default: 10)</div>
                <InputError message={form.errors.priority} />
              </FormField>
            )}
            <FormField>
              <Label htmlFor="ttl">TTL (Time To Live)</Label>
              <Select
                value={manualTtl ? 'manual' : form.data.ttl.toString()}
                onValueChange={(value) => {
                  if (value === 'manual') {
                    setManualTtl(true);
                    form.setData('ttl', 300);
                  } else {
                    setManualTtl(false);
                    form.setData('ttl', parseInt(value));
                  }
                }}
              >
                <SelectTrigger id="ttl">
                  <SelectValue placeholder="Select TTL" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="1">Auto</SelectItem>
                  <SelectItem value="300">300 (5 minutes)</SelectItem>
                  <SelectItem value="600">600 (10 minutes)</SelectItem>
                  <SelectItem value="1800">1800 (30 minutes)</SelectItem>
                  <SelectItem value="3600">3600 (1 hour)</SelectItem>
                  <SelectItem value="7200">7200 (2 hours)</SelectItem>
                  <SelectItem value="14400">14400 (4 hours)</SelectItem>
                  <SelectItem value="28800">28800 (8 hours)</SelectItem>
                  <SelectItem value="43200">43200 (12 hours)</SelectItem>
                  <SelectItem value="86400">86400 (1 day)</SelectItem>
                  <SelectItem value="manual">Manual</SelectItem>
                </SelectContent>
              </Select>
              {manualTtl && (
                <Input
                  type="number"
                  value={form.data.ttl}
                  onChange={(e) => form.setData('ttl', parseInt(e.target.value) || 1)}
                  placeholder="300"
                  min={1}
                  max={86400}
                  className="mt-2"
                />
              )}
              <div className="text-muted-foreground text-xs">How long DNS servers should cache this record</div>
              <InputError message={form.errors.ttl} />
            </FormField>
            {proxyTypes.includes(form.data.type) && (
              <FormField>
                <div className="flex items-center space-x-3">
                  <Checkbox id="proxied" name="proxied" checked={form.data.proxied} onClick={() => form.setData('proxied', !form.data.proxied)} />
                  <Label htmlFor="proxied">Proxied (CDN enabled)</Label>
                </div>
                <InputError message={form.errors.proxied} />
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
          <Button type="button" onClick={submit} disabled={form.processing}>
            {form.processing && <LoaderCircle className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            {record ? 'Save' : 'Create Record'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
