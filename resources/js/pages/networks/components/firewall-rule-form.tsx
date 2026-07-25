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
import { NetworkFirewallRule } from '@/types/network';

export default function NetworkFirewallRuleForm({
  open,
  onOpenChange,
  networkId,
  rule,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  networkId: number;
  rule?: NetworkFirewallRule;
}) {
  const form = useForm<{
    name: string;
    protocol: string;
    port: string;
  }>({
    name: rule?.name || '',
    protocol: rule?.protocol || '',
    port: rule?.port || '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (rule) {
      form.put(route('networks.firewall.update', { network: networkId, networkFirewallRule: rule.id }), {
        onSuccess: () => onOpenChange(false),
      });
      return;
    }
    form.post(route('networks.firewall.store', { network: networkId }), {
      onSuccess: () => onOpenChange(false),
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>{rule ? 'Edit' : 'Create'} firewall rule</DialogTitle>
          <DialogDescription className="sr-only">{rule ? 'Edit' : 'Create new'} network firewall rule</DialogDescription>
        </DialogHeader>
        <Form id="network-firewall-rule-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="name">Name</Label>
              <Input id="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
              <InputError message={form.errors.name} />
            </FormField>

            <FormField>
              <Label htmlFor="protocol">Protocol</Label>
              <Select
                value={form.data.protocol === '' ? 'all' : form.data.protocol}
                onValueChange={(value) => form.setData('protocol', value === 'all' ? '' : value)}
              >
                <SelectTrigger className="w-full">
                  <SelectValue placeholder="All protocols" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    <SelectItem value="all">All protocols</SelectItem>
                    <SelectItem value="tcp">TCP</SelectItem>
                    <SelectItem value="udp">UDP</SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>
              <InputError message={form.errors.protocol} />
            </FormField>

            <FormField>
              <Label htmlFor="port">Port</Label>
              <Input
                id="port"
                placeholder="e.g. 3306 or 3000:3010 (leave empty for all)"
                value={form.data.port}
                onChange={(e) => form.setData('port', e.target.value)}
              />
              <p className="text-muted-foreground text-xs">
                Protocol and port are independent — leave either empty to match all of it, or both to match all traffic from the network.
              </p>
              <InputError message={form.errors.port} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Close</Button>
          </DialogClose>
          <Button form="network-firewall-rule-form" type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
