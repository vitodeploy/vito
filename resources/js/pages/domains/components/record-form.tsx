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
import { useForm } from '@inertiajs/react';
import { FormEventHandler, ReactNode, useState } from 'react';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/ui/input-error';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { Domain } from '@/types/domain';
import { DNSRecord } from '@/types/dns-record';
import FormSuccessful from '@/components/form-successful';

interface RecordFormProps {
  domain: Domain;
  record?: DNSRecord;
  children: ReactNode;
}

export default function RecordForm({ domain, record, children }: RecordFormProps) {
  const [open, setOpen] = useState(false);

  const form = useForm({
    type: record?.type || 'A',
    name: record?.name || '',
    content: record?.content || '',
    ttl: record?.ttl || 1,
    proxied: record?.proxied || false,
  });

  const submit: FormEventHandler = (e) => {
    e.preventDefault();

    if (record) {
      // Edit existing record
      form.patch(route('dns-records.update', [record.domain_id, record.id]), {
        onSuccess: () => {
          setOpen(false);
        },
      });
    } else {
      // Create new record
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
    <Dialog open={open} onOpenChange={setOpen}>
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
              <Input
                type="text"
                id="content"
                name="content"
                value={form.data.content}
                onChange={(e) => form.setData('content', e.target.value)}
                placeholder={getContentPlaceholder()}
              />
              <div className="text-muted-foreground text-xs">{getContentDescription()}</div>
              <InputError message={form.errors.content} />
            </FormField>
            <FormField>
              <Label htmlFor="ttl">TTL (Time To Live)</Label>
              <Select value={form.data.ttl.toString()} onValueChange={(value) => form.setData('ttl', parseInt(value))}>
                <SelectTrigger id="ttl">
                  <SelectValue placeholder="Select TTL" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="1">Auto</SelectItem>
                  <SelectItem value="300">5 minutes</SelectItem>
                  <SelectItem value="600">10 minutes</SelectItem>
                  <SelectItem value="1800">30 minutes</SelectItem>
                  <SelectItem value="3600">1 hour</SelectItem>
                  <SelectItem value="7200">2 hours</SelectItem>
                  <SelectItem value="14400">4 hours</SelectItem>
                  <SelectItem value="28800">8 hours</SelectItem>
                  <SelectItem value="43200">12 hours</SelectItem>
                  <SelectItem value="86400">1 day</SelectItem>
                </SelectContent>
              </Select>
              <div className="text-muted-foreground text-xs">How long DNS servers should cache this record</div>
              <InputError message={form.errors.ttl} />
            </FormField>
            <FormField>
              <div className="flex items-center space-x-3">
                <Checkbox id="proxied" name="proxied" checked={form.data.proxied} onClick={() => form.setData('proxied', !form.data.proxied)} />
                <Label htmlFor="proxied">Proxied (CDN enabled)</Label>
              </div>
              <InputError message={form.errors.proxied} />
            </FormField>
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
