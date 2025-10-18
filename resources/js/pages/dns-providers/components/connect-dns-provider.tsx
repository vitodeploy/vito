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
import { FormEventHandler, ReactNode, useEffect, useState } from 'react';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/ui/input-error';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { SharedData } from '@/types';
import { Checkbox } from '@/components/ui/checkbox';
import { DynamicFieldConfig } from '@/types/dynamic-field-config';
import DynamicField from '@/components/ui/dynamic-field';

type DNSProviderForm = {
  provider: string;
  name: string;
  global: boolean;
};

export default function ConnectDNSProvider({
  defaultProvider,
  onProviderAdded,
  children,
}: {
  defaultProvider?: string;
  onProviderAdded?: () => void;
  children: ReactNode;
}) {
  const [open, setOpen] = useState(false);

  const page = usePage<SharedData>();

  const form = useForm<Required<DNSProviderForm>>({
    provider: defaultProvider || 'cloudflare',
    name: '',
    global: false,
  });

  const submit: FormEventHandler = (e) => {
    e.preventDefault();
    form.post(route('dns-providers.store'), {
      onSuccess: () => {
        setOpen(false);
        if (onProviderAdded) {
          onProviderAdded();
        }
      },
    });
  };

  useEffect(() => {
    const providerConfig = page.props.configs.dns_provider.providers[form.data.provider];
    if (providerConfig?.form) {
      providerConfig.form.forEach((field: DynamicFieldConfig) => {
        /* @ts-expect-error dynamic types */
        if (field.default !== undefined && (form.data[field.name] === '' || form.data[field.name] === undefined)) {
          /* @ts-expect-error dynamic types */
          form.setData(field.name, field.default);
        }
      });
    }
  }, [form.data.provider]);

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="max-h-screen overflow-y-auto sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Connect to DNS provider</DialogTitle>
          <DialogDescription className="sr-only">Connect to a new DNS provider</DialogDescription>
        </DialogHeader>
        <Form id="create-dns-provider-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="provider">Provider</Label>
              <Select
                value={form.data.provider}
                onValueChange={(value) => {
                  form.setData('provider', value);
                  form.clearErrors();
                }}
              >
                <SelectTrigger id="provider">
                  <SelectValue placeholder="Select a provider" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    {Object.entries(page.props.configs.dns_provider.providers).map(
                      ([key, provider]) =>
                        key !== 'custom' && (
                          <SelectItem key={key} value={key}>
                            {provider.label}
                          </SelectItem>
                        ),
                    )}
                  </SelectGroup>
                </SelectContent>
              </Select>
              <InputError message={form.errors.provider} />
            </FormField>
            <FormField>
              <Label htmlFor="name">Profile Name</Label>
              <Input type="text" name="name" id="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
              <InputError message={form.errors.name} />
            </FormField>
            {page.props.configs.dns_provider.providers[form.data.provider]?.form?.map((field: DynamicFieldConfig) => (
              <DynamicField
                key={`field-${field.name}`}
                /*@ts-expect-error dynamic types*/
                value={form.data[field.name]}
                /*@ts-expect-error dynamic types*/
                onChange={(value) => form.setData(field.name, value)}
                config={field}
                /*@ts-expect-error dynamic types*/
                error={form.errors[field.name]}
              />
            ))}
            <FormField>
              <div className="flex items-center space-x-3">
                <Checkbox id="global" name="global" checked={form.data.global} onClick={() => form.setData('global', !form.data.global)} />
                <Label htmlFor="global">Is global (accessible in all projects)</Label>
              </div>
              <InputError message={form.errors.global} />
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
            Connect
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
