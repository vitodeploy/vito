import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { FormEvent } from 'react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Service } from '@/types/service';

export default function Fail2banForm({
  open,
  onOpenChange,
  serverId,
  fail2ban,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  serverId: number;
  fail2ban?: Service | null;
}) {
  const data = (fail2ban?.type_data ?? {}) as Record<string, string | number>;
  const isInstalled = !!fail2ban;

  const form = useForm<{
    maxretry: string;
    findtime: string;
    bantime: string;
    ignoreip: string;
  }>({
    maxretry: String(data.maxretry ?? 5),
    findtime: String(data.findtime ?? '10m'),
    bantime: String(data.bantime ?? '10m'),
    ignoreip: String(data.ignoreip ?? ''),
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    const onSuccess = () => onOpenChange(false);
    if (isInstalled) {
      form.patch(route('security.fail2ban.update', { server: serverId }), { preserveScroll: true, onSuccess });
      return;
    }
    form.post(route('security.fail2ban.install', { server: serverId }), { preserveScroll: true, onSuccess });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>{isInstalled ? 'Configure Fail2ban' : 'Install Fail2ban'}</DialogTitle>
          <DialogDescription>Protect SSH from brute-force attacks by banning repeated failed logins.</DialogDescription>
        </DialogHeader>
        <Form id="fail2ban-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="maxretry">Max retries</Label>
              <Input type="number" id="maxretry" min={1} value={form.data.maxretry} onChange={(e) => form.setData('maxretry', e.target.value)} />
              <p className="text-muted-foreground text-xs">Failed attempts allowed within the find-time window before a ban.</p>
              <InputError message={form.errors.maxretry} />
            </FormField>

            <FormField>
              <Label htmlFor="findtime">Find time</Label>
              <Input
                type="text"
                id="findtime"
                placeholder="10m"
                value={form.data.findtime}
                onChange={(e) => form.setData('findtime', e.target.value)}
              />
              <p className="text-muted-foreground text-xs">Window in which failures are counted (e.g. 10m, 1h).</p>
              <InputError message={form.errors.findtime} />
            </FormField>

            <FormField>
              <Label htmlFor="bantime">Ban time</Label>
              <Input type="text" id="bantime" placeholder="10m" value={form.data.bantime} onChange={(e) => form.setData('bantime', e.target.value)} />
              <p className="text-muted-foreground text-xs">How long an IP stays banned (e.g. 10m, 1h, 1d, -1 for permanent).</p>
              <InputError message={form.errors.bantime} />
            </FormField>

            <FormField>
              <Label htmlFor="ignoreip">Ignored IPs</Label>
              <Input
                type="text"
                id="ignoreip"
                placeholder="203.0.113.10 198.51.100.0/24"
                value={form.data.ignoreip}
                onChange={(e) => form.setData('ignoreip', e.target.value)}
              />
              <p className="text-muted-foreground text-xs">
                Space-separated IPs/CIDRs that are never banned. Localhost and Vito&apos;s own address are always allowed.
              </p>
              <InputError message={form.errors.ignoreip} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Close</Button>
          </DialogClose>
          <Button form="fail2ban-form" type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            {isInstalled ? 'Save' : 'Install'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
