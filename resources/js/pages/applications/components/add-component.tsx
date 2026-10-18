import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DialogControlProps } from '@/components/dialogs/registry';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Checkbox } from '@/components/ui/checkbox';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/ui/input-error';
import DynamicField from '@/components/ui/dynamic-field';
import { LoaderCircleIcon } from 'lucide-react';
import { Application, ComponentOptions, Environment } from '@/types/application';
import { DynamicFieldValue } from '@/types/dynamic-field-config';

export default function AddComponent({ open, onOpenChange, application, environment, options }: DialogControlProps & {
  application: Application;
  environment: Environment;
  options: ComponentOptions;
}) {
  const form = useForm({
    name: '',
    server_id: '',
    user: '',
    type: 'app',
    capabilities: [] as { capability: string; config: Record<string, DynamicFieldValue> }[],
  });
  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(`/applications/${application.id}/environments/${environment.id}/components`, { onSuccess: () => onOpenChange(false) });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-xl" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Add component</DialogTitle>
          <DialogDescription>Place an app component in {environment.name}. For a deployed environment, its live release follows once installation completes when deployments are available.</DialogDescription>
        </DialogHeader>
        <Form id="add-component-form" className="p-4" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="component-name">Name</Label>
              <Input id="component-name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
              <InputError message={form.errors.name} />
            </FormField>
            <FormField>
              <Label htmlFor="component-server">Server</Label>
              <Select value={form.data.server_id} onValueChange={(value) => form.setData((data) => ({ ...data, server_id: value, user: !data.user || data.user === options.servers.find((server) => String(server.id) === data.server_id)?.ssh_user ? options.servers.find((server) => String(server.id) === value)?.ssh_user ?? '' : data.user }))}>
                <SelectTrigger id="component-server"><SelectValue placeholder="Select a ready server" /></SelectTrigger>
                <SelectContent>{options.servers.map((server) => <SelectItem key={server.id} value={String(server.id)}>{server.name}</SelectItem>)}</SelectContent>
              </Select>
              <InputError message={form.errors.server_id} />
            </FormField>
            <FormField>
              <Label htmlFor="component-user">Linux user</Label>
              <Input id="component-user" value={form.data.user} onChange={(e) => form.setData('user', e.target.value)} autoComplete="off" />
              <p className="text-muted-foreground text-sm">Use the server SSH user or an isolated user. Required services must already be installed.</p>
              <InputError message={form.errors.user} />
            </FormField>
            <div className="flex flex-col gap-4">
              <p className="font-medium">Capabilities</p>
              <p className="text-muted-foreground text-sm">Configuration is saved now; capability installation will be available in a future update.</p>
              {options.capabilities.map((option) => {
                const selected = form.data.capabilities.find((capability) => capability.capability === option.id);
                return (
                  <div className="flex flex-col gap-3 rounded-md border p-3" key={option.id}>
                    <div className="flex items-center gap-2">
                      <Checkbox id={`capability-${option.id}`} checked={!!selected} onCheckedChange={(checked) => form.setData('capabilities', checked === true
                        ? [...form.data.capabilities, { capability: option.id, config: Object.fromEntries(option.form.map((field) => [field.name, field.default ?? ''])) }]
                        : form.data.capabilities.filter((capability) => capability.capability !== option.id))} />
                      <Label htmlFor={`capability-${option.id}`}>{option.label}</Label>
                    </div>
                    <p className="text-muted-foreground text-sm">{option.description}</p>
                    {selected && option.form.map((field) => <DynamicField key={field.name} config={{ ...field, name: `${option.id}-${field.name}` }} value={selected.config[field.name]} onChange={(value) => form.setData('capabilities', form.data.capabilities.map((capability) => capability.capability === option.id
                      ? { ...capability, config: { ...capability.config, [field.name]: value } } : capability))} />)}
                  </div>
                );
              })}
              <InputError message={form.errors.capabilities} />
            </div>
            <InputError message={form.errors.type} />
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild><Button variant="outline">Cancel</Button></DialogClose>
          <Button form="add-component-form" type="submit" disabled={form.processing || !form.data.server_id}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}Add component
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
