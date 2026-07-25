import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { FormEvent } from 'react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Combobox } from '@/components/ui/combobox';
import { NetworkServerOption } from '@/types/network';
import PrivateIpSelect from './private-ip-select';
import { useServerWithPrivateIp } from '@/hooks/use-server-with-private-ip';

type CreateNetworkForm = {
  name: string;
  type: string;
  servers: number[];
  addressing_pool: string;
  prefix: string;
  port: string;
  cidr: string;
  ip_addresses: Record<number, number>;
};

export default function CreateNetwork({
  open,
  onOpenChange,
  servers: initialServers,
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
    ip_addresses: {},
  });

  const isCustom = form.data.type === 'custom';

  const {
    servers,
    selectedServerId: primaryServerId,
    selectedServer: primaryServer,
    selectServer: selectPrimaryServer,
    selectIp,
    applyRefreshedIps,
    selectedIp,
    ipError,
  } = useServerWithPrivateIp(form, initialServers);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('networks.store'), {
      onSuccess: () => {
        onOpenChange(false);
        form.reset();
      },
    });
  };

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
              <Select
                value={form.data.type}
                onValueChange={(value) => form.setData((prev) => ({ ...prev, type: value, servers: [], ip_addresses: {} }))}
              >
                <SelectTrigger className="w-full">
                  <SelectValue placeholder="Select type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    <SelectItem value="wireguard">WireGuard</SelectItem>
                    <SelectItem value="custom">Custom</SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>
              <InputError message={form.errors.type} />
            </FormField>

            {!isCustom && (
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

                <FormField>
                  <Label htmlFor="prefix">Block size (/prefix)</Label>
                  <Input id="prefix" value={form.data.prefix} onChange={(e) => form.setData('prefix', e.target.value)} />
                  <InputError message={form.errors.prefix} />
                </FormField>

                <FormField>
                  <Label htmlFor="port">Listen port</Label>
                  <Input id="port" value={form.data.port} onChange={(e) => form.setData('port', e.target.value)} />
                  <p className="text-muted-foreground text-xs">
                    The UDP port the tunnel listens on. Vito moves to the next free port if a selected server already uses this one for another
                    network.
                  </p>
                  <InputError message={form.errors.port} />
                </FormField>
              </>
            )}

            {isCustom && (
              <FormField>
                <Label htmlFor="cidr">CIDR (optional)</Label>
                <Input
                  id="cidr"
                  placeholder="e.g. 10.0.0.0/24 or fd00:1::/64"
                  value={form.data.cidr}
                  onChange={(e) => form.setData('cidr', e.target.value)}
                />
                <InputError message={form.errors.cidr} />
              </FormField>
            )}

            <FormField>
              <Label htmlFor="primary-server">Primary server</Label>
              <Combobox
                id="primary-server"
                items={servers.map((server) => ({
                  value: String(server.id),
                  label: server.is_ready ? server.name : `${server.name} (not ready)`,
                  keywords: [server.name],
                }))}
                value={primaryServerId ? String(primaryServerId) : ''}
                placeholder="Select a server"
                searchText="Filter servers..."
                noneFoundText="No servers found."
                onValueChange={(value) => selectPrimaryServer(Number(value))}
              />
              <InputError message={form.errors.servers} />
            </FormField>

            {isCustom && (
              <FormField>
                <Label htmlFor="primary-server-ip">Private IP</Label>
                <PrivateIpSelect
                  serverId={primaryServerId}
                  ips={primaryServer?.private_ips ?? []}
                  value={selectedIp}
                  onValueChange={selectIp}
                  onRefreshed={applyRefreshedIps}
                />
                <InputError message={ipError} />
              </FormField>
            )}
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
