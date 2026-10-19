import { FormEvent } from 'react';
import { useForm, usePage } from '@inertiajs/react';
import { loadBalancerMethodLabel } from '@/lib/utils';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DialogControlProps } from '@/components/dialogs/registry';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/ui/input-error';
import { LoaderCircleIcon, PlusIcon, Trash2Icon } from 'lucide-react';
import { Application, BackendTarget, Component, Environment } from '@/types/application';

export default function LoadBalancerBackends({ open, onOpenChange, application, environment, component, targets: initialTargets }: DialogControlProps & {
  application: Application;
  environment: Environment;
  component: Component;
  targets: BackendTarget[];
}) {
  const page = usePage<{ application?: Application; environment?: Environment; backendTargets?: BackendTarget[] }>().props;
  const targets = page.application?.id === application.id && page.environment?.id === environment.id ? page.backendTargets ?? initialTargets : initialTargets;
  const form = useForm({
    method: component.load_balancer_method ?? 'round-robin',
    backends: (component.backends ?? []).map((backend) => ({
      target_component_id: backend.target_component_id,
      ip: backend.ip,
      port: String(backend.port),
      weight: backend.weight === null ? '' : String(backend.weight),
      backup: backend.backup,
    })),
  });
  const errors = form.errors as Record<string, string>;
  const caddy = component.webserver === 'caddy';
  const update = (index: number, value: Partial<(typeof form.data.backends)[number]>) => {
    form.setData('backends', form.data.backends.map((backend, current) => current === index ? { ...backend, ...value } : backend));
  };
  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.put(`/applications/${application.id}/environments/${environment.id}/components/${component.id}/backends`, { onSuccess: () => onOpenChange(false) });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl" onCloseAutoFocus={(event) => event.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Load balancer backends</DialogTitle>
          <DialogDescription>Component backends use a shared private network (or loopback on the same server). Their backend port is opened only to this load balancer. External IP backends have no Vito-managed firewall; secure them yourself.</DialogDescription>
        </DialogHeader>
        <Form id="load-balancer-backends-form" className="p-4" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="lb-method">Method</Label>
              <Select disabled={form.processing} value={form.data.method} onValueChange={(method) => form.setData((data) => ({ ...data, method, backends: data.backends.map((backend) => ({ ...backend, backup: method === 'ip-hash' ? false : backend.backup })) }))}>
                <SelectTrigger id="lb-method"><SelectValue /></SelectTrigger>
                <SelectContent>
                  {['round-robin', 'least-connections', 'ip-hash'].map((method) => <SelectItem key={method} value={method}>{loadBalancerMethodLabel(method)}</SelectItem>)}
                </SelectContent>
              </Select>
              <InputError message={errors.method} />
            </FormField>
            {caddy && <p className="text-muted-foreground text-sm">Caddy supports equal-weight primary backends only. Weight and backup settings are unavailable.</p>}
            {form.data.backends.map((backend, index) => <fieldset key={index} className="flex flex-col gap-3 rounded-md border p-3" disabled={form.processing}>
              <legend className="px-1 text-sm font-medium">Backend {index + 1}</legend>
              <FormField>
                <Label htmlFor={`lb-target-${index}`}>Target</Label>
                <Select value={backend.target_component_id === null ? 'external' : String(backend.target_component_id)} onValueChange={(value) => update(index, { target_component_id: value === 'external' ? null : Number(value), ip: value === 'external' ? '' : null })}>
                  <SelectTrigger id={`lb-target-${index}`}><SelectValue /></SelectTrigger>
                  <SelectContent>
                    <SelectItem value="external">External IP address</SelectItem>
                    {targets.map((target) => <SelectItem key={target.id} value={String(target.id)}>{target.name} ({target.server_name})</SelectItem>)}
                    {backend.target_component_id !== null && !targets.some((target) => target.id === backend.target_component_id) && <SelectItem value={String(backend.target_component_id)} disabled>Unavailable component #{backend.target_component_id}</SelectItem>}
                  </SelectContent>
                </Select>
                <InputError message={errors[`backends.${index}.target_component_id`]} />
              </FormField>
              {backend.target_component_id === null && <FormField>
                <Label htmlFor={`lb-ip-${index}`}>IP address</Label>
                <Input id={`lb-ip-${index}`} value={backend.ip ?? ''} onChange={(event) => update(index, { ip: event.target.value })} />
                <InputError message={errors[`backends.${index}.ip`]} />
              </FormField>}
              <div className="grid gap-3 sm:grid-cols-2">
                <FormField>
                  <Label htmlFor={`lb-port-${index}`}>Backend port</Label>
                  <Input id={`lb-port-${index}`} type="number" min={backend.target_component_id === null ? 1 : 1024} max={65535} value={backend.port} onChange={(event) => update(index, { port: event.target.value })} />
                  <InputError message={errors[`backends.${index}.port`]} />
                </FormField>
                <FormField>
                  <Label htmlFor={`lb-weight-${index}`}>Weight</Label>
                  <Input id={`lb-weight-${index}`} type="number" min={1} max={1000} placeholder="1" disabled={caddy} value={backend.weight} onChange={(event) => update(index, { weight: event.target.value })} />
                  <InputError message={errors[`backends.${index}.weight`]} />
                </FormField>
              </div>
              <div className="flex items-center gap-2">
                <Checkbox id={`lb-backup-${index}`} checked={backend.backup} disabled={caddy || form.data.method === 'ip-hash'} onCheckedChange={(checked) => update(index, { backup: checked === true })} />
                <Label htmlFor={`lb-backup-${index}`}>Backup (unavailable with IP hash)</Label>
              </div>
              <InputError message={errors[`backends.${index}.backup`] ?? errors[`backends.${index}`]} />
              <Button type="button" variant="outline" onClick={() => form.setData('backends', form.data.backends.filter((_, current) => current !== index))}><Trash2Icon />Remove backend {index + 1}</Button>
            </fieldset>)}
            <p className="text-muted-foreground text-sm">For component targets, choose an unused port, not the app's existing process port. An empty backend list stops forwarding traffic.</p>
            <Button type="button" variant="outline" disabled={form.processing || form.data.backends.length >= 100} onClick={() => form.setData('backends', [...form.data.backends, { target_component_id: targets[0]?.id ?? null, ip: targets.length ? null : '', port: '8080', weight: '', backup: false }])}><PlusIcon />Add backend</Button>
            <InputError message={errors.backends} />
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild><Button variant="outline">Cancel</Button></DialogClose>
          <Button form="load-balancer-backends-form" type="submit" disabled={form.processing}>{form.processing && <LoaderCircleIcon className="animate-spin" />}Save</Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
