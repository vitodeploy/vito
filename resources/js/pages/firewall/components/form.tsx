import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { FormEvent } from 'react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { InfoIcon, LoaderCircleIcon } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { FirewallRule } from '@/types/firewall';

export default function RuleForm({
  open,
  onOpenChange,
  serverId,
  firewallRule,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  serverId: number;
  firewallRule?: FirewallRule;
}) {
  const form = useForm<{
    name: string;
    type: string;
    protocol: string;
    port: string;
    source_any: boolean;
    source: string;
    mask: string;
  }>({
    name: firewallRule?.name || '',
    type: firewallRule?.type || '',
    protocol: firewallRule?.protocol || '',
    port: firewallRule?.port?.toString() || '',
    source_any: !firewallRule?.source,
    source: firewallRule?.source || '',
    mask: firewallRule?.mask?.toString() || '32',
  });

  const changeSource = (source: string) => {
    const hostMask = source.includes(':') ? '128' : '32';

    form.setData((prev) => ({
      ...prev,
      source,
      mask: prev.mask === '' || prev.mask === '32' || prev.mask === '128' ? hostMask : prev.mask,
    }));
  };

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (firewallRule) {
      form.put(route('firewall.update', { server: serverId, firewallRule: firewallRule.id }), {
        onSuccess: () => onOpenChange(false),
      });
      return;
    }

    form.post(route('firewall.store', { server: serverId }), {
      onSuccess: () => onOpenChange(false),
    });
  };
  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>{firewallRule ? 'Edit' : 'Create'} firewall rule</DialogTitle>
          <DialogDescription className="sr-only">{firewallRule ? 'Edit' : 'Create new'} firewall rule</DialogDescription>
        </DialogHeader>
        <Form id="firewall-rule-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="name">Name</Label>
              <Input type="text" id="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
              <InputError message={form.errors.name} />
            </FormField>

            <FormField>
              <Label htmlFor="type">Type</Label>
              <Select onValueChange={(value) => form.setData('type', value)} value={form.data.type}>
                <SelectTrigger className="w-full">
                  <SelectValue placeholder="Select type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    <SelectItem value="allow">Allow</SelectItem>
                    <SelectItem value="deny">Deny</SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>
              <InputError message={form.errors.type} />
            </FormField>

            <FormField>
              <Label htmlFor="protocol">Protocol</Label>
              <Select onValueChange={(value) => form.setData('protocol', value)} value={form.data.protocol}>
                <SelectTrigger className="w-full">
                  <SelectValue placeholder="Select protocol" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
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
                type="text"
                id="port"
                placeholder="e.g. 8080 or 3000:3010"
                value={form.data.port}
                onChange={(e) => form.setData('port', e.target.value)}
              />
              <p className="text-muted-foreground text-xs">
                Enter a single port (e.g. <code>8080</code>) or a range (e.g. <code>3000:3010</code>). Ranges are inclusive.
              </p>
              <InputError message={form.errors.port} />
            </FormField>

            <FormField>
              <div className="flex items-center space-x-3">
                <Checkbox id="source_any" checked={form.data.source_any} onClick={() => form.setData('source_any', !form.data.source_any)} />
                <Label htmlFor="source_any">Any source</Label>
              </div>
            </FormField>

            {!form.data.source_any && (
              <>
                <FormField>
                  <Label htmlFor="source">Source</Label>
                  <Input type="text" id="source" value={form.data.source} onChange={(e) => changeSource(e.target.value)} />
                  <InputError message={form.errors.source} />
                </FormField>

                <FormField>
                  <Label htmlFor="mask">Mask</Label>
                  <Input type="text" id="mask" value={form.data.mask} onChange={(e) => form.setData('mask', e.target.value)} />
                  <Alert>
                    <InfoIcon />
                    <AlertDescription>
                      <p>
                        The mask sets how many IP addresses this rule covers. Use <code>{form.data.source.includes(':') ? '128' : '32'}</code> for
                        just this one IP, <code>{form.data.source.includes(':') ? '64' : '24'}</code> for its whole local network, and smaller numbers
                        to cover even more. Lower number = wider range.
                      </p>
                    </AlertDescription>
                  </Alert>
                  <InputError message={form.errors.mask} />
                </FormField>
              </>
            )}
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Close</Button>
          </DialogClose>
          <Button form="firewall-rule-form" type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
