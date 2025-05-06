import { LoaderCircle, PlusIcon, WifiIcon } from 'lucide-react';
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
import { FormEventHandler, useEffect, useState } from 'react';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/input-error';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { SharedData } from '@/types';
import { Checkbox } from '@/components/ui/checkbox';

type ServerProviderForm = {
  provider: string;
  name: string;
  global: boolean;
};

export default function CreateServerProvider({
  trigger,
  providers,
  defaultProvider,
  onProviderAdded,
}: {
  trigger: 'icon' | 'button';
  providers: string[];
  defaultProvider?: string;
  onProviderAdded?: () => void;
}) {
  const [open, setOpen] = useState(false);

  const page = usePage<SharedData>();

  const form = useForm<Required<ServerProviderForm>>({
    provider: 'aws',
    name: '',
    global: false,
  });

  const submit: FormEventHandler = (e) => {
    e.preventDefault();
    form.post(route('server-providers.store'), {
      onSuccess: () => {
        setOpen(false);
        if (onProviderAdded) {
          onProviderAdded();
        }
      },
    });
  };

  useEffect(() => {
    form.setData('provider', defaultProvider ?? 'aws');
  }, [defaultProvider]);

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button type="button" variant="outline">
          {trigger === 'icon' && <WifiIcon />}
          {trigger === 'button' && (
            <>
              <PlusIcon />
              Connect
            </>
          )}
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-xl">
        <DialogHeader>
          <DialogTitle>Connect</DialogTitle>
          <DialogDescription>Connect to a new server provider</DialogDescription>
        </DialogHeader>
        <Form id="create-server-provider-form" onSubmit={submit} className="py-4">
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
                    {providers.map((provider) => (
                      <SelectItem key={provider} value={provider}>
                        {provider}
                      </SelectItem>
                    ))}
                  </SelectGroup>
                </SelectContent>
              </Select>
              <InputError message={form.errors.provider} />
            </FormField>
            <FormField>
              <Label htmlFor="name">Name</Label>
              <Input
                type="text"
                name="name"
                id="name"
                placeholder="Name"
                value={form.data.name}
                onChange={(e) => form.setData('name', e.target.value)}
              />
              <InputError message={form.errors.name} />
            </FormField>
            {page.props.configs.server_providers_custom_fields[form.data.provider]?.map((item: string) => (
              <FormField key={item}>
                <Label htmlFor={item}>{item}</Label>
                <Input
                  type="text"
                  name={item}
                  id={item}
                  placeholder={item}
                  value={(form.data[item as keyof ServerProviderForm] as string) ?? ''}
                  onChange={(e) => form.setData(item as keyof ServerProviderForm, e.target.value)}
                />
                <InputError message={form.errors[item as keyof ServerProviderForm]} />
              </FormField>
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
