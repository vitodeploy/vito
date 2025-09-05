import { ClipboardCheckIcon, ClipboardIcon, LoaderCircle, PlusIcon, TrashIcon, TriangleAlert, WifiIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { useForm, usePage } from '@inertiajs/react';
import React, { FormEventHandler, useEffect, useState } from 'react';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/ui/input-error';
import { Input } from '@/components/ui/input';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { ServerProvider } from '@/types/server-provider';
import ConnectServerProvider from '@/pages/server-providers/components/connect-server-provider';
import axios from 'axios';
import { Form, FormField, FormFields } from '@/components/ui/form';
import type { SharedData } from '@/types';
import { DataTable } from '@/components/data-table';
import { ColumnDef } from '@tanstack/react-table';
import { EventBus } from '@/lib/event-bus';
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
import ServerTemplates from './templates';
import { ServerTemplate, Service } from '@/types/server-template';
import { Textarea } from '@/components/ui/textarea';

type CreateServerForm = {
  provider: string;
  server_provider: number;
  name: string;
  os: string;
  ip: string;
  port: number;
  region: string;
  plan: string;
  services: Service[];
};

function AddService() {
  const [open, setOpen] = useState(false);
  const page = usePage<SharedData>();
  const form = useForm<Service>({
    type: '',
    name: '',
    version: '',
  });

  const add = () => {
    if (!form.data.name) {
      form.setError('name', 'Please select a service name');
      return;
    }

    if (!form.data.version) {
      form.setError('version', 'Please select a service version');
      return;
    }

    EventBus.emit('add-service', form.data);
    setOpen(false);
  };

  return (
    <Dialog modal open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <div className="flex items-center justify-end p-0">
          <button type="button" className="cursor-pointer">
            <PlusIcon className="size-4" />
          </button>
        </div>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Add service</DialogTitle>
          <DialogDescription className="sr-only">Add a new service to server installation</DialogDescription>
        </DialogHeader>

        <Form id="add-service-form" onSubmit={add} className="p-4">
          <FormFields>
            {/*service*/}
            <FormField>
              <Label htmlFor="name">Name</Label>
              <Select
                value={form.data.name}
                onValueChange={(value) => {
                  form.setData('name', value);
                  form.setData('type', page.props.configs.service.services[value].type);
                  form.setData('version', '');
                }}
              >
                <SelectTrigger id="name">
                  <SelectValue placeholder="Select a service" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    {Object.entries(page.props.configs.service.services).map(([key, service]) => (
                      <SelectItem key={`service-${key}`} value={key}>
                        {service.label}
                      </SelectItem>
                    ))}
                  </SelectGroup>
                </SelectContent>
              </Select>
              <InputError message={form.errors.type || form.errors.name} />
            </FormField>

            {/*version*/}
            <FormField>
              <Label htmlFor="version">Version</Label>
              <Select value={form.data.version} onValueChange={(value) => form.setData('version', value)}>
                <SelectTrigger id="version">
                  <SelectValue placeholder="Select a version" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    {form.data.name &&
                      page.props.configs.service.services[form.data.name].versions.map((version) => (
                        <SelectItem key={`version-${form.data.name}-${version}`} value={version}>
                          {version}
                        </SelectItem>
                      ))}
                  </SelectGroup>
                </SelectContent>
              </Select>
              <InputError message={form.errors.version} />
            </FormField>
          </FormFields>
        </Form>

        <DialogFooter>
          <DialogClose asChild>
            <Button type="button" variant="outline">
              Cancel
            </Button>
          </DialogClose>
          <Button form="add-service-form" type="button" onClick={add}>
            Add
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

const servicesColumns: ColumnDef<Service>[] = [
  {
    accessorKey: 'type',
    header: 'Type',
  },
  {
    accessorKey: 'name',
    header: 'Name',
  },
  {
    accessorKey: 'version',
    header: 'Version',
  },
  {
    id: 'actions',
    header: () => <AddService />,
    enableSorting: false,
    cell: ({ row }) => (
      <div className="flex items-center justify-end">
        <button type="button" className="hover:text-destructive" onClick={() => EventBus.emit('remove-service', row.original)}>
          <TrashIcon className="size-4" />
        </button>
      </div>
    ),
  },
];

export default function CreateServer({
  defaultOpen,
  onOpenChange,
  children,
}: {
  defaultOpen?: boolean;
  onOpenChange?: (open: boolean) => void;
  children: React.ReactNode;
}) {
  const page = usePage<SharedData>();

  const [open, setOpen] = useState(defaultOpen || false);
  useEffect(() => {
    if (defaultOpen) {
      setOpen(defaultOpen);
    }

    const handleRemoveService = (d: unknown) => {
      const service = d as Service;
      form.setData((data) => ({
        ...data,
        services: data.services.filter((s) => s.type !== service.type || s.name !== service.name || s.version !== service.version),
      }));
    };
    EventBus.on('remove-service', handleRemoveService);

    const handleAddService = (d: unknown) => {
      const service = d as Service;
      form.setData((data) => ({
        ...data,
        services: [...data.services, service],
      }));
    };
    EventBus.on('add-service', handleAddService);

    return () => {
      EventBus.off('remove-service', handleRemoveService);
      EventBus.off('add-service', handleAddService);
    };
  }, [defaultOpen]);

  const handleOpenChange = (open: boolean) => {
    setOpen(open);
    if (onOpenChange) {
      onOpenChange(open);
    }
  };

  const form = useForm<Required<CreateServerForm>>({
    provider: 'custom',
    server_provider: 0,
    name: '',
    os: '',
    ip: '',
    port: 22,
    region: '',
    plan: '',
    services: [
      {
        type: 'webserver',
        name: 'nginx',
        version: 'latest',
      },
      {
        type: 'database',
        name: 'mysql',
        version: '8.4',
      },
      {
        type: 'memory_database',
        name: 'redis',
        version: 'latest',
      },
      {
        type: 'process_manager',
        name: 'supervisor',
        version: 'latest',
      },
      {
        type: 'firewall',
        name: 'ufw',
        version: 'latest',
      },
      {
        type: 'monitoring',
        name: 'remote-monitor',
        version: 'latest',
      },
    ],
  });

  const submit: FormEventHandler = (e) => {
    e.preventDefault();
    form.post(route('servers'));
  };

  const [copySuccess, setCopySuccess] = useState(false);
  const copyToClipboard = () => {
    navigator.clipboard.writeText(page.props.public_key_text).then(() => {
      setCopySuccess(true);
      setTimeout(() => {
        setCopySuccess(false);
      }, 2000);
    });
  };

  const [serverProviders, setServerProviders] = useState<ServerProvider[]>([]);
  const fetchServerProviders = async () => {
    const serverProviders = await axios.get(route('server-providers.json'));
    setServerProviders(serverProviders.data);
  };
  const selectProvider = (provider: string) => {
    form.setData('provider', provider);
    form.clearErrors();
    if (provider !== 'custom') {
      form.setData('server_provider', 0);
      form.setData('region', '');
      form.setData('plan', '');
      fetchServerProviders();
    }
  };

  const selectServerProvider = async (serverProvider: string) => {
    form.setData('server_provider', parseInt(serverProvider));
    await fetchRegions(parseInt(serverProvider));
  };

  const [regions, setRegions] = useState<{ [key: string]: string }>({});
  const fetchRegions = async (serverProvider: number) => {
    const regions = await axios.get(route('server-providers.regions', { serverProvider: serverProvider }));
    setRegions(regions.data);
  };
  const selectRegion = async (region: string) => {
    form.setData('region', region);
    if (region !== '') {
      await fetchPlans(form.data.server_provider, region);
    }
  };

  const [plans, setPlans] = useState<{ [key: string]: string }>({});
  const fetchPlans = async (serverProvider: number, region: string) => {
    const plans = await axios.get(route('server-providers.plans', { serverProvider: serverProvider, region: region }));
    setPlans(plans.data);
  };
  const selectPlan = (plan: string) => {
    form.setData('plan', plan);
  };

  const serverTemplateChanged = (template: ServerTemplate | null) => {
    if (template) {
      form.setData('services', template.services);
    } else {
      form.setData('services', []);
    }
  };

  return (
    <Sheet open={open} onOpenChange={handleOpenChange} modal>
      <SheetTrigger asChild>{children}</SheetTrigger>
      <SheetContent className="w-full lg:max-w-4xl">
        <SheetHeader>
          <SheetTitle>Create new server</SheetTitle> <SheetDescription>Fill in the details to create a new server.</SheetDescription>
        </SheetHeader>
        <Form id="create-server-form" className="p-4" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="provider">Provider</Label>
              <Select value={form.data.provider} onValueChange={(value) => selectProvider(value)}>
                <SelectTrigger id="provider">
                  <SelectValue placeholder="Select a provider" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    {Object.entries(page.props.configs.server_provider.providers).map(([key, provider]) => (
                      <SelectItem key={key} value={key}>
                        {provider.label}
                      </SelectItem>
                    ))}
                  </SelectGroup>
                </SelectContent>
              </Select>
              <InputError message={form.errors.provider} />
            </FormField>

            {form.data.provider && form.data.provider !== 'custom' && (
              <FormField>
                <Label htmlFor="server-provider">Server provider connection</Label>
                <div className="flex items-center gap-2">
                  <Select value={form.data.server_provider.toString()} onValueChange={selectServerProvider}>
                    <SelectTrigger id="provider">
                      <SelectValue placeholder="Select a provider" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        {serverProviders
                          .filter((item: ServerProvider) => item.provider === form.data.provider)
                          .map((provider) => (
                            <SelectItem key={`server-provider-${provider.id}`} value={provider.id.toString()}>
                              {provider.name}
                            </SelectItem>
                          ))}
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  <ConnectServerProvider defaultProvider={form.data.provider} onProviderAdded={fetchServerProviders}>
                    <Button variant="outline">
                      <WifiIcon />
                    </Button>
                  </ConnectServerProvider>
                </div>
                <InputError message={form.errors.server_provider} />
              </FormField>
            )}

            {form.data.provider && form.data.provider !== 'custom' && (
              <div className="grid grid-cols-2 gap-6">
                <FormField>
                  <Label htmlFor="region">Region</Label>
                  <Select value={form.data.region} onValueChange={selectRegion} disabled={form.data.server_provider === 0}>
                    <SelectTrigger id="region">
                      <SelectValue placeholder="Select a region" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        {Object.entries(regions).map(([key, value]) => (
                          <SelectItem key={`region-${key}`} value={key}>
                            {value}
                          </SelectItem>
                        ))}
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  <InputError message={form.errors.region} />
                </FormField>

                <FormField>
                  <Label htmlFor="plan">Plan</Label>
                  <Select value={form.data.plan} onValueChange={selectPlan} disabled={form.data.region === ''}>
                    <SelectTrigger id="plan">
                      <SelectValue placeholder="Select a plan" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        {Object.entries(plans).map(([key, value]) => (
                          <SelectItem key={`plan-${key}`} value={key}>
                            {value}
                          </SelectItem>
                        ))}
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  <InputError message={form.errors.plan} />
                </FormField>
              </div>
            )}

            {form.data.provider === 'custom' && (
              <>
                <Alert>
                  <TriangleAlert size={5} />
                  <AlertDescription>
                    Your server needs to have a new unused installation of supported operating systems and must have a root user. To get started, add
                    our public key to /root/.ssh/authorized_keys file by running the bellow command on your server as root.
                  </AlertDescription>
                </Alert>
                <FormField>
                  <Label htmlFor="public_key" className="flex items-center gap-2">
                    Public Key command
                    {copySuccess ? <ClipboardCheckIcon className="text-success! size-3" /> : <ClipboardIcon className="size-3 cursor-pointer" />}
                  </Label>
                  <Textarea
                    onClick={copyToClipboard}
                    id="public_key"
                    value={page.props.public_key_text}
                    className="justify-between overflow-auto font-normal"
                    spellCheck={false}
                  ></Textarea>
                </FormField>
              </>
            )}

            <div className="grid grid-cols-2 items-start gap-6">
              <FormField>
                <Label htmlFor="name">Server Name</Label>
                <Input id="name" type="text" autoComplete="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                <InputError message={form.errors.name} />
              </FormField>
              <FormField>
                <Label htmlFor="os">Operating System</Label>
                <Select value={form.data.os} onValueChange={(value) => form.setData('os', value)}>
                  <SelectTrigger id="os">
                    <SelectValue placeholder="Select an operating system" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      {page.props.configs.operating_systems.map((value) => (
                        <SelectItem key={`os-${value}`} value={value}>
                          {value}
                        </SelectItem>
                      ))}
                    </SelectGroup>
                  </SelectContent>
                </Select>
                <InputError message={form.errors.os} />
              </FormField>
            </div>

            {form.data.provider === 'custom' && (
              <div className="grid grid-cols-2 items-start gap-6">
                <FormField>
                  <Label htmlFor="ip">SSH IP</Label>
                  <Input id="ip" type="text" autoComplete="ip" value={form.data.ip} onChange={(e) => form.setData('ip', e.target.value)} />
                  <InputError message={form.errors.ip} />
                </FormField>

                <FormField>
                  <Label htmlFor="port">SSH Port</Label>
                  <Input
                    id="port"
                    type="text"
                    autoComplete="port"
                    value={form.data.port}
                    onChange={(e) => form.setData('port', parseInt(e.target.value))}
                  />
                  <InputError message={form.errors.port} />
                </FormField>
              </div>
            )}

            <div>
              <FormField>
                <div className="flex items-center justify-between">
                  <Label>Services</Label>
                  <ServerTemplates services={form.data.services} onTemplateChanged={serverTemplateChanged} />
                </div>
                <div>
                  <DataTable columns={servicesColumns} data={form.data.services} />
                </div>
                {Object.entries(form.errors)
                  .filter(([key, value]) => {
                    return key.startsWith('services') && value.length > 0;
                  })
                  .map(([key, value]) => (
                    <InputError key={key} message={value} />
                  ))}
              </FormField>
            </div>
          </FormFields>
        </Form>
        <SheetFooter>
          <div className="flex items-center gap-2">
            <Button type="submit" form="create-server-form" tabIndex={4} disabled={form.processing}>
              {form.processing && <LoaderCircle className="animate-spin" />} Create
            </Button>
            <SheetClose asChild>
              <Button variant="outline" disabled={form.processing}>
                Cancel
              </Button>
            </SheetClose>
          </div>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}
