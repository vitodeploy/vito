import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { FormEvent } from 'react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon, TriangleAlertIcon } from 'lucide-react';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { NetworkServerOption } from '@/types/network';

type AddServerForm = {
  servers: number[];
  ip_addresses: Record<number, number>;
};

export default function AddServer({
  open,
  onOpenChange,
  networkId,
  isProvider,
  servers,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  networkId: number;
  isProvider: boolean;
  servers: NetworkServerOption[];
}) {
  const form = useForm<AddServerForm>({
    servers: [],
    ip_addresses: {},
  });

  const toggleServer = (id: number) => {
    const selected = form.data.servers.includes(id);
    form.setData('servers', selected ? form.data.servers.filter((s) => s !== id) : [...form.data.servers, id]);
  };

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
          <DialogTitle>Add servers</DialogTitle>
          <DialogDescription className="sr-only">Add servers to the network</DialogDescription>
        </DialogHeader>
        <Form id="add-server-form" onSubmit={submit} className="max-h-[70vh] overflow-y-auto p-4">
          <FormFields>
            <FormField>
              <Label>Servers</Label>
              <div className="flex flex-col gap-3">
                {servers.length === 0 && <p className="text-muted-foreground text-sm">No eligible servers available.</p>}
                {servers.map((server) => {
                  const selected = form.data.servers.includes(server.id);
                  const selectedIp = form.data.ip_addresses[server.id];
                  const primaryChosen = server.private_ips.find((ip) => ip.id === selectedIp)?.is_primary;
                  return (
                    <div key={server.id} className="flex flex-col gap-2 rounded-md border p-3">
                      <div className="flex items-center gap-3">
                        <Checkbox id={`add-server-${server.id}`} checked={selected} onClick={() => toggleServer(server.id)} />
                        <Label htmlFor={`add-server-${server.id}`} className="flex-1">
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
