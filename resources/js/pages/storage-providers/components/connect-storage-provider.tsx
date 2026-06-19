import { Info, LoaderCircle } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
import { Checkbox } from '@/components/ui/checkbox';
import { DynamicFieldConfig } from '@/types/dynamic-field-config';
import DynamicField from '@/components/ui/dynamic-field';
import { useConfigs } from '@/stores/bootstrap-store';
import { SharedData } from '@/types';

type StorageProviderForm = {
  provider: string;
  name: string;
  global: boolean;
  app_key: string;
  app_secret: string;
};

export default function ConnectStorageProvider({
  defaultProvider,
  onProviderAdded,
  children,
}: {
  defaultProvider?: string;
  onProviderAdded?: () => void;
  children: ReactNode;
}) {
  const [open, setOpen] = useState(false);

  const configs = useConfigs()!;
  const csrfToken = usePage<SharedData>().props.csrf_token;

  const form = useForm<Required<StorageProviderForm>>({
    provider: defaultProvider || 'local',
    name: '',
    global: false,
    app_key: '',
    app_secret: '',
  });

  const submit: FormEventHandler = (e) => {
    e.preventDefault();

    if (form.data.provider === 'dropbox') {
      redirectToDropbox();
      return;
    }

    form.post(route('storage-providers.store'), {
      onSuccess: () => {
        setOpen(false);
        if (onProviderAdded) {
          onProviderAdded();
        }
      },
    });
  };

  const redirectToDropbox = () => {
    const errors: Partial<Record<keyof StorageProviderForm, string>> = {};
    if (!form.data.name) {
      errors.name = 'The name field is required.';
    }
    if (!form.data.app_key) {
      errors.app_key = 'The app key field is required.';
    }
    if (!form.data.app_secret) {
      errors.app_secret = 'The app secret field is required.';
    }
    if (Object.keys(errors).length > 0) {
      form.setError(errors);
      return;
    }

    const values: Record<string, string> = {
      _token: csrfToken,
      name: form.data.name,
      app_key: form.data.app_key,
      app_secret: form.data.app_secret,
      global: form.data.global ? '1' : '',
    };

    const nativeForm = document.createElement('form');
    nativeForm.method = 'POST';
    nativeForm.action = route('storage-providers.dropbox.redirect');
    nativeForm.style.display = 'none';

    Object.entries(values).forEach(([name, value]) => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      input.value = value ?? '';
      nativeForm.appendChild(input);
    });

    document.body.appendChild(nativeForm);
    nativeForm.submit();
  };

  useEffect(() => {
    const providerConfig = configs.storage_provider.providers[form.data.provider];
    if (providerConfig?.form) {
      providerConfig.form.forEach((field: DynamicFieldConfig) => {
        /* @ts-expect-error dynamic types */
        if (field.default !== undefined && (form.data[field.name] === '' || form.data[field.name] === undefined)) {
          /* @ts-expect-error dynamic types */
          form.setData(field.name, field.default);
        }
      });
    }
  }, [form.data.provider, configs]);

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="max-h-screen overflow-y-auto sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Connect to storage provider</DialogTitle>
          <DialogDescription className="sr-only">Connect to a new storage provider</DialogDescription>
        </DialogHeader>
        <Form id="create-storage-provider-form" onSubmit={submit} className="p-4">
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
                    {Object.entries(configs.storage_provider.providers).map(([key, provider]) => (
                      <SelectItem key={key} value={key}>
                        {provider.label}
                      </SelectItem>
                    ))}
                  </SelectGroup>
                </SelectContent>
              </Select>
              <InputError message={form.errors.provider} />
            </FormField>
            {form.data.provider === 'dropbox' && (
              <Alert>
                <Info />
                <AlertTitle>Connect with Dropbox OAuth</AlertTitle>
                <AlertDescription>
                  <p>Create a Dropbox app with offline access enabled, then add this redirect URI to its OAuth settings:</p>
                  <code className="bg-muted block w-full rounded px-1.5 py-1 text-xs break-all">{route('storage-providers.dropbox.callback')}</code>
                  <p>Enter the app key and secret below, then continue to Dropbox to authorize access.</p>
                </AlertDescription>
              </Alert>
            )}
            <FormField>
              <Label htmlFor="name">Name</Label>
              <Input type="text" name="name" id="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
              <InputError message={form.errors.name} />
            </FormField>
            {configs.storage_provider.providers[form.data.provider]?.form?.map((field: DynamicFieldConfig) => (
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
