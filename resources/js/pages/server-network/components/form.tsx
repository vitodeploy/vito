import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { FormEvent, useState } from 'react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

export default function ServerIpForm({
  open,
  onOpenChange,
  serverId,
  interfaces = [],
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  serverId: number;
  interfaces?: string[];
}) {
  const [customMask, setCustomMask] = useState(false);
  const form = useForm<{
    ip: string;
    prefix_length: string;
    interface: string;
  }>({
    ip: '',
    prefix_length: '',
    interface: interfaces.includes('eth0') ? 'eth0' : (interfaces[0] ?? 'eth0'),
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.transform((data) => ({ ...data, prefix_length: customMask ? data.prefix_length : '' }));
    form.post(route('servers.network.ips.store', { server: serverId }), {
      onSuccess: () => onOpenChange(false),
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Add IP address</DialogTitle>
          <DialogDescription className="sr-only">Add an IP address to this server</DialogDescription>
        </DialogHeader>
        <Form id="server-ip-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="ip">IP Address</Label>
              <Input type="text" id="ip" placeholder="e.g. 203.0.113.10" value={form.data.ip} onChange={(e) => form.setData('ip', e.target.value)} />
              <InputError message={form.errors.ip} />
            </FormField>

            <FormField>
              <Label htmlFor="interface">Interface</Label>
              {interfaces.length > 0 ? (
                <Select onValueChange={(value) => form.setData('interface', value)} value={form.data.interface}>
                  <SelectTrigger id="interface" className="w-full">
                    <SelectValue placeholder="Select interface" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      {interfaces.map((iface) => (
                        <SelectItem key={iface} value={iface}>
                          {iface}
                        </SelectItem>
                      ))}
                    </SelectGroup>
                  </SelectContent>
                </Select>
              ) : (
                <Input
                  type="text"
                  id="interface"
                  placeholder="e.g. eth0"
                  value={form.data.interface}
                  onChange={(e) => form.setData('interface', e.target.value)}
                />
              )}
              <InputError message={form.errors.interface} />
            </FormField>

            <FormField>
              <div className="flex items-center space-x-3">
                <Checkbox id="custom_mask" checked={customMask} onClick={() => setCustomMask(!customMask)} />
                <Label htmlFor="custom_mask">Set a custom subnet mask</Label>
              </div>
              <p className="text-muted-foreground text-xs">
                Defaults to <code>/32</code> for IPv4 and <code>/64</code> for IPv6. Only change this if the address belongs to a specific subnet.
              </p>
            </FormField>

            {customMask && (
              <FormField>
                <Label htmlFor="prefix_length">Subnet mask (prefix length)</Label>
                <Input
                  type="text"
                  id="prefix_length"
                  placeholder="e.g. 24"
                  value={form.data.prefix_length}
                  onChange={(e) => form.setData('prefix_length', e.target.value)}
                />
                <InputError message={form.errors.prefix_length} />
              </FormField>
            )}
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Close</Button>
          </DialogClose>
          <Button form="server-ip-form" type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
