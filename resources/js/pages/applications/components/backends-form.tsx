import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { NetworkIcon, PlusIcon, Trash2Icon } from 'lucide-react';
import { cn, loadBalancerMethodLabel } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Application, BackendTarget, Component, Environment } from '@/types/application';
import { EmptyState, Field, SaveActions, Section, useSyncedDefaults } from './section';

export default function BackendsForm({ application, environment, component, targets, disabled }: {
  application: Application;
  environment: Environment;
  component: Component;
  targets: BackendTarget[];
  disabled: boolean;
}) {
  const values = {
    method: component.load_balancer_method ?? 'round-robin',
    backends: (component.backends ?? []).map((backend) => ({
      target_component_id: backend.target_component_id,
      ip: backend.ip,
      port: String(backend.port),
      weight: backend.weight === null ? '' : String(backend.weight),
      backup: backend.backup,
    })),
  };
  const form = useForm(values);
  useSyncedDefaults(form, values);
  const errors = form.errors as Record<string, string>;
  const caddy = component.webserver === 'caddy';
  const update = (index: number, value: Partial<(typeof form.data.backends)[number]>) => {
    form.setData('backends', form.data.backends.map((backend, current) => current === index ? { ...backend, ...value } : backend));
  };
  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.put(`/applications/${application.id}/environments/${environment.id}/components/${component.id}/backends`, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => form.setDefaults(),
    });
  };

  const columns = caddy ? 'grid-cols-[minmax(0,1fr)_6rem_2rem]' : 'grid-cols-[minmax(0,1fr)_6rem_5rem_4.5rem_2rem]';
  const add = () => form.setData('backends', [...form.data.backends, { target_component_id: targets[0]?.id ?? null, ip: targets.length ? null : '', port: '8080', weight: '', backup: false }]);

  return (
    <form onSubmit={submit}>
      <fieldset disabled={disabled || form.processing} className="contents">
        <Section
          title="Backends"
          description={<>Each target opens its port to this load balancer only. Pick a port the app isn't already using.{caddy && ' Caddy balances equally, so weight and backup are not available.'}</>}
          action={!disabled && <Button type="button" size="sm" variant="outline" disabled={form.processing || form.data.backends.length >= 100} onClick={add}><PlusIcon />Add backend</Button>}
          footer={!disabled && <SaveActions form={form} />}
        >
          <Field inline id="lb-method" label="Balancing method" error={errors.method}>
            <Select disabled={disabled} value={form.data.method} onValueChange={(method) => form.setData((data) => ({ ...data, method, backends: data.backends.map((backend) => ({ ...backend, backup: method === 'ip-hash' ? false : backend.backup })) }))}>
              <SelectTrigger id="lb-method"><SelectValue /></SelectTrigger>
              <SelectContent>
                {['round-robin', 'least-connections', 'ip-hash'].map((method) => <SelectItem key={method} value={method}>{loadBalancerMethodLabel(method)}</SelectItem>)}
              </SelectContent>
            </Select>
          </Field>
          {form.data.backends.length === 0 ? (
            <EmptyState className="rounded-none border-0" icon={NetworkIcon} title="No backends" description="This load balancer is not forwarding traffic." />
          ) : (
            <div className={cn('text-muted-foreground grid items-center gap-3 px-4 py-2 text-xs font-medium', columns)}>
              <span>Target</span>
              <span>Port</span>
              {!caddy && <><span>Weight</span><span>Backup</span></>}
            </div>
          )}
          {form.data.backends.map((backend, index) => (
            <div key={index} className="flex flex-col gap-2 px-4 py-3">
              <div className={cn('grid items-start gap-3', columns)}>
                <div className="flex min-w-0 flex-col gap-2">
                  <Select disabled={disabled} value={backend.target_component_id === null ? 'external' : String(backend.target_component_id)} onValueChange={(value) => update(index, { target_component_id: value === 'external' ? null : Number(value), ip: value === 'external' ? '' : null })}>
                    <SelectTrigger aria-label={`Backend ${index + 1} target`}><SelectValue /></SelectTrigger>
                    <SelectContent>
                      {targets.map((target) => <SelectItem key={target.id} value={String(target.id)}>{target.name} on {target.server_name}</SelectItem>)}
                      {backend.target_component_id !== null && !targets.some((target) => target.id === backend.target_component_id) && <SelectItem value={String(backend.target_component_id)} disabled>Unavailable component #{backend.target_component_id}</SelectItem>}
                      <SelectItem value="external">External IP address</SelectItem>
                    </SelectContent>
                  </Select>
                  {backend.target_component_id === null && (
                    <Input aria-label={`Backend ${index + 1} IP address`} placeholder="IP address, not managed by Vito's firewall" value={backend.ip ?? ''} onChange={(event) => update(index, { ip: event.target.value })} />
                  )}
                </div>
                <Input aria-label={`Backend ${index + 1} port`} type="number" min={backend.target_component_id === null ? 1 : 1024} max={65535} value={backend.port} onChange={(event) => update(index, { port: event.target.value })} />
                {!caddy && (
                  <>
                    <Input aria-label={`Backend ${index + 1} weight`} type="number" min={1} max={1000} placeholder="1" value={backend.weight} onChange={(event) => update(index, { weight: event.target.value })} />
                    <div className="flex h-9 items-center">
                      <Checkbox aria-label={`Backend ${index + 1} is a backup`} checked={backend.backup} disabled={disabled || form.data.method === 'ip-hash'} onCheckedChange={(checked) => update(index, { backup: checked === true })} />
                    </div>
                  </>
                )}
                {!disabled && (
                  <Button type="button" variant="ghost" size="icon" className="size-9" aria-label={`Remove backend ${index + 1}`} onClick={() => form.setData('backends', form.data.backends.filter((_, current) => current !== index))}>
                    <Trash2Icon />
                  </Button>
                )}
              </div>
              <InputError message={errors[`backends.${index}.target_component_id`] ?? errors[`backends.${index}.ip`] ?? errors[`backends.${index}.port`] ?? errors[`backends.${index}.weight`] ?? errors[`backends.${index}.backup`] ?? errors[`backends.${index}`]} />
            </div>
          ))}
          {errors.backends && <div className="px-4 py-3"><InputError message={errors.backends} /></div>}
        </Section>
      </fieldset>
    </form>
  );
}
