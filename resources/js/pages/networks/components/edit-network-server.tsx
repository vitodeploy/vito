import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { FormEvent, useState } from 'react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { NetworkMemberIp } from '@/types/network';
import PrivateIpSelect, { PrivateIp } from './private-ip-select';

export default function EditNetworkServer({
  open,
  onOpenChange,
  networkId,
  member,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  networkId: number;
  member: NetworkMemberIp;
}) {
  const [ips, setIps] = useState<PrivateIp[]>(member.private_ips);

  const form = useForm<{ server_ip_address_id: number | null }>({
    server_ip_address_id: member.ip_address_id,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.put(route('networks.servers.update', { network: networkId, networkServer: member.id }), {
      preserveScroll: true,
      onSuccess: () => onOpenChange(false),
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Edit {member.server_name}</DialogTitle>
          <DialogDescription className="sr-only">Change the private IP this server uses on the network</DialogDescription>
        </DialogHeader>
        <Form id="edit-network-server-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="server_ip_address_id">Private IP</Label>
              <PrivateIpSelect
                serverId={member.server_id}
                ips={ips}
                value={form.data.server_ip_address_id ?? undefined}
                onValueChange={(id) => form.setData('server_ip_address_id', id)}
                onRefreshed={(_serverId, refreshed) => setIps(refreshed)}
                refreshSource={{
                  only: ['memberIps', 'flash'],
                  resolve: (props, serverId) =>
                    (props.memberIps as NetworkMemberIp[] | undefined)?.find((m) => m.server_id === serverId)?.private_ips,
                }}
              />
              <InputError message={form.errors.server_ip_address_id} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Close</Button>
          </DialogClose>
          <Button form="edit-network-server-form" type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
