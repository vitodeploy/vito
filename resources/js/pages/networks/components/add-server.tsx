import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { FormEvent } from 'react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Combobox } from '@/components/ui/combobox';
import { NetworkServerOption } from '@/types/network';
import PrivateIpSelect from './private-ip-select';
import { useServerWithPrivateIp } from '@/hooks/use-server-with-private-ip';

type AddServerForm = {
  servers: number[];
  ip_addresses: Record<number, number>;
};

export default function AddServer({
  open,
  onOpenChange,
  networkId,
  isCustom,
  servers: initialServers,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  networkId: number;
  isCustom: boolean;
  servers: NetworkServerOption[];
}) {
  const form = useForm<AddServerForm>({
    servers: [],
    ip_addresses: {},
  });

  const { servers, selectedServerId, selectedServer, selectServer, selectIp, applyRefreshedIps, selectedIp, ipError } = useServerWithPrivateIp(
    form,
    initialServers,
  );

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('networks.servers.store', { network: networkId }), {
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
          <DialogTitle>Add server</DialogTitle>
          <DialogDescription className="sr-only">Add a server to the network</DialogDescription>
        </DialogHeader>
        <Form id="add-server-form" onSubmit={submit} className="max-h-[70vh] overflow-y-auto p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="add-server">Server</Label>
              <Combobox
                id="add-server"
                items={servers.map((server) => ({
                  value: String(server.id),
                  label: server.is_ready ? server.name : `${server.name} (not ready)`,
                  keywords: [server.name],
                }))}
                value={selectedServerId ? String(selectedServerId) : ''}
                placeholder="Select a server"
                searchText="Filter servers..."
                noneFoundText="No eligible servers available."
                onValueChange={(value) => selectServer(Number(value))}
              />
              <InputError message={form.errors.servers} />
            </FormField>

            {isCustom && (
              <FormField>
                <Label htmlFor="add-server-ip">Private IP</Label>
                <PrivateIpSelect
                  serverId={selectedServerId}
                  ips={selectedServer?.private_ips ?? []}
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
          <Button form="add-server-form" type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Add
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
