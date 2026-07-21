import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { FormEvent } from 'react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon, TriangleAlertIcon } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { NetworkServerOption } from '@/types/network';

type CreateNetworkForm = {
  name: string;
  type: string;
  servers: number[];
  addressing_pool: string;
  prefix: string;
  port: string;
  cidr: string;
  firewall_enabled: boolean;
  ip_addresses: Record<number, number>;
};

export default function CreateNetwork({
  open,
  onOpenChange,
  servers,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  servers: NetworkServerOption[];
}) {
  const form = useForm<CreateNetworkForm>({
    name: '',
    type: 'wireguard',
    servers: [],
    addressing_pool: 'cgnat',
    prefix: '24',
    port: '51820',
    cidr: '',
    firewall_enabled: false,
    ip_addresses: {},
  });

  const toggleServer = (id: number) => {
    const selected = form.data.servers.includes(id);
    form.setData('servers', selected ? form.data.servers.filter((s) => s !== id) : [...form.data.servers, id]);
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('networks.store'), {
      onSuccess: () => {
        onOpenChange(false);
        form.reset();
      },
    });
  };

  const isProvider = form.data.type === 'provider';

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Create network</DialogTitle>
          <DialogDescription className="sr-only">Create a new private network</DialogDescription>
        </DialogHeader>
        <Form id="create-network-form" onSubmit={submit} className="max-h-[70vh] overflow-y-auto p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="name">Name</Label>
              <Input id="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
              <InputError message={form.errors.name} />
            </FormField>

            <FormField>
              <Label htmlFor="type">Type</Label>
              <Select value={form.data.type} onValueChange={(value) => form.setData('type', value)}>
                <SelectTrigger className="w-full">
                  <SelectValue placeholder="Select type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    <SelectItem value="wireguard">WireGuard (Vito-managed)</SelectItem>
                    <SelectItem value="provider">Provider Managed</SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>
              <InputError message={form.errors.type} />
            </FormField>

            {!isProvider && (
              <>
                <FormField>
                  <Label htmlFor="addressing_pool">Address pool</Label>
                  <Select value={form.data.addressing_pool} onValueChange={(value) => form.setData('addressing_pool', value)}>
                    <SelectTrigger className="w-full">
                      <SelectValue placeholder="Select pool" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectItem value="cgnat">CGNAT (100.64.0.0/10)</SelectItem>
                        <SelectItem value="rfc1918">Private (RFC1918)</SelectItem>
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  <InputError message={form.errors.addressing_pool} />
                </FormField>

                <div className="grid grid-cols-2 gap-4">
                  <FormField>
                    <Label htmlFor="prefix">Block size (/prefix)</Label>
                    <Input id="prefix" value={form.data.prefix} onChange={(e) => form.setData('prefix', e.target.value)} />
                    <InputError message={form.errors.prefix} />
                  </FormField>
                  <FormField>
                    <Label htmlFor="port">Listen port</Label>
                    <Input id="port" value={form.data.port} onChange={(e) => form.setData('port', e.target.value)} />
                    <InputError message={form.errors.port} />
                  </FormField>
                </div>
              </>
            )}

            {isProvider && (
              <FormField>
                <Label htmlFor="cidr">CIDR (optional)</Label>
                <Input id="cidr" placeholder="e.g. 10.0.0.0/24" value={form.data.cidr} onChange={(e) => form.setData('cidr', e.target.value)} />
                <InputError message={form.errors.cidr} />
              </FormField>
            )}

            <FormField>
              <Label>Servers</Label>
              <div className="flex flex-col gap-3">
                {servers.map((server) => {
                  const selected = form.data.servers.includes(server.id);
                  const selectedIp = form.data.ip_addresses[server.id];
                  const primaryChosen = server.private_ips.find((ip) => ip.id === selectedIp)?.is_primary;
                  return (
                    <div key={server.id} className="flex flex-col gap-2 rounded-md border p-3">
                      <div className="flex items-center gap-3">
                        <Checkbox id={`server-${server.id}`} checked={selected} onClick={() => toggleServer(server.id)} />
                        <Label htmlFor={`server-${server.id}`} className="flex-1">
                          {server.name}
                        </Label>
                        {!server.is_ready && <span className="text-muted-foreground text-xs">not ready</span>}
                      </div>
                      {selected && isProvider && (
                        <div className="pl-7">
                          <Select
                            value={selectedIp ? String(selectedIp) : ''}
                            onValueChange={(value) => form.setData('ip_addresses', { ...form.data.ip_addresses, [server.id]: Number(value) })}
                          >
                            <SelectTrigger className="w-full">
                              <SelectValue placeholder="Select a private IP" />
                            </SelectTrigger>
                            <SelectContent>
                              <SelectGroup>
                                {server.private_ips.map((ip) => (
                                  <SelectItem key={ip.id} value={String(ip.id)}>
                                    {ip.ip}
                                    {ip.is_primary ? ' (primary)' : ''}
                                  </SelectItem>
                                ))}
                              </SelectGroup>
                            </SelectContent>
                          </Select>
                          {primaryChosen && (
                            <Alert className="mt-2">
                              <TriangleAlertIcon />
                              <AlertDescription>This is the server&apos;s primary IP. Make sure you intend to use it for this network.</AlertDescription>
                            </Alert>
                          )}
                          <InputError message={form.errors[`ip_addresses.${server.id}` as keyof typeof form.errors]} />
                        </div>
                      )}
                    </div>
                  );
                })}
              </div>
              <InputError message={form.errors.servers} />
            </FormField>

            <FormField>
              <div className="flex items-center gap-3">
                <Checkbox
                  id="firewall_enabled"
                  checked={form.data.firewall_enabled}
                  onClick={() => form.setData('firewall_enabled', !form.data.firewall_enabled)}
                />
                <Label htmlFor="firewall_enabled">Manage firewall for this network</Label>
              </div>
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Close</Button>
          </DialogClose>
          <Button form="create-network-form" type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Create
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
