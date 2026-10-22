import { FormEvent } from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import { LoaderCircleIcon, TriangleAlertIcon } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { DialogControlProps } from '@/components/dialogs/registry';
import { Button } from '@/components/ui/button';
import { Combobox } from '@/components/ui/combobox';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group';
import { Application, Environment, EnvironmentResourceOptions } from '@/types/application';

export default function AttachResource({
  open,
  onOpenChange,
  application,
  environment,
  options: initialOptions,
  kind,
}: DialogControlProps & {
  application: Application;
  environment: Environment;
  options: EnvironmentResourceOptions;
  kind: 'database' | 'redis';
}) {
  const page = usePage<{ application?: Application; environment?: Environment; resourceOptions?: EnvironmentResourceOptions }>().props;
  const options =
    page.application?.id === application.id && page.environment?.id === environment.id ? (page.resourceOptions ?? initialOptions) : initialOptions;
  const redis = kind === 'redis';
  const services = options.services.filter((service) => (redis ? service.type === 'memory_database' : service.type === 'database'));
  const form = useForm({
    mode: redis ? 'redis' : 'new',
    service_id: '',
    server_id: '',
    database_id: '',
    name: '',
    charset: '',
    collation: '',
    alias: '',
  });
  const service = services.find((item) => item.id.toString() === form.data.service_id);
  const defaultCollation = (charsets: EnvironmentResourceOptions['services'][number]['charsets'] | undefined, charset: string) => {
    const collations = charsets?.[charset];
    return collations?.default && collations.list.includes(collations.default) ? collations.default : (collations?.list[0] ?? '');
  };
  const selectService = (id: string) => {
    const selected = services.find((item) => item.id.toString() === id);
    const charset = selected?.charsets.utf8mb4 ? 'utf8mb4' : (selected?.default_charset ?? '');
    form.setData({
      ...form.data,
      service_id: id,
      server_id: selected?.server_id.toString() ?? '',
      database_id: '',
      charset,
      collation: defaultCollation(selected?.charsets, charset),
    });
  };
  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.transform((data) =>
      redis
        ? { service_id: data.service_id, alias: data.alias }
        : data.mode === 'existing'
          ? { server_id: data.server_id, database_id: data.database_id, alias: data.alias }
          : { server_id: data.server_id, name: data.name, charset: data.charset, collation: data.collation, alias: data.alias },
    );
    form.post(`/applications/${application.id}/environments/${environment.id}/resources/${redis ? 'redis' : 'database'}`, {
      onSuccess: () => onOpenChange(false),
    });
  };
  const incomplete =
    !service ||
    (form.data.mode === 'existing' && !form.data.database_id) ||
    (form.data.mode === 'new' && (!form.data.name || !form.data.charset || !form.data.collation));

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>{redis ? 'Add Redis / Valkey' : 'Add database'}</DialogTitle>
          <DialogDescription>
            Vito creates dedicated credentials (existing users are never reused) and injects them into every app component on the next deploy.
          </DialogDescription>
        </DialogHeader>
        <Form id="attach-resource-form" className="p-4" onSubmit={submit}>
          <FormFields>
            {!redis && (
              <ToggleGroup
                type="single"
                variant="outline"
                className="w-full"
                value={form.data.mode}
                disabled={form.processing}
                onValueChange={(mode) => mode && form.setData({ ...form.data, mode, database_id: '' })}
              >
                <ToggleGroupItem value="new" className="flex-1">
                  New database
                </ToggleGroupItem>
                <ToggleGroupItem value="existing" className="flex-1">
                  Existing database
                </ToggleGroupItem>
              </ToggleGroup>
            )}
            <FormField>
              <Label htmlFor="resource-service">Server</Label>
              <Select value={form.data.service_id} disabled={form.processing} onValueChange={selectService}>
                <SelectTrigger id="resource-service">
                  <SelectValue placeholder={services.length ? 'Select a server' : 'No servers available'} />
                </SelectTrigger>
                <SelectContent>
                  {services.map((item) => (
                    <SelectItem key={item.id} value={item.id.toString()}>
                      {item.server_name} · {item.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {services.length === 0 && (
                <p className="text-muted-foreground text-xs">
                  Install {redis ? 'Redis or Valkey' : 'a database service'} and a firewall on a{' '}
                  <Link href="/servers" className="underline">
                    project server
                  </Link>{' '}
                  first.
                </p>
              )}
            </FormField>
            {form.data.mode === 'existing' && (
              <FormField>
                <Label htmlFor="resource-database">Database</Label>
                <Select
                  value={form.data.database_id}
                  disabled={form.processing || !service}
                  onValueChange={(value) => form.setData('database_id', value)}
                >
                  <SelectTrigger id="resource-database">
                    <SelectValue placeholder="Select a database" />
                  </SelectTrigger>
                  <SelectContent>
                    {service?.databases.map((database) => (
                      <SelectItem key={database.id} value={database.id.toString()}>
                        {database.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </FormField>
            )}
            {form.data.mode === 'new' && (
              <>
                <FormField>
                  <Label htmlFor="resource-name">Database name</Label>
                  <Input
                    id="resource-name"
                    required
                    value={form.data.name}
                    disabled={form.processing}
                    onChange={(e) => form.setData('name', e.target.value)}
                    placeholder={`${application.name}_${environment.slug}`.replace(/[^a-z0-9_]/gi, '_').toLowerCase()}
                  />
                </FormField>
                {service && (
                  <div className="grid gap-6 sm:grid-cols-2">
                    <FormField>
                      <Label htmlFor="resource-charset">Charset</Label>
                      <Select
                        value={form.data.charset}
                        disabled={form.processing}
                        onValueChange={(charset) =>
                          charset && form.setData({ ...form.data, charset, collation: defaultCollation(service.charsets, charset) })
                        }
                      >
                        <SelectTrigger id="resource-charset">
                          <SelectValue placeholder="Charset" />
                        </SelectTrigger>
                        <SelectContent>
                          {Object.keys(service.charsets).map((charset) => (
                            <SelectItem key={charset} value={charset}>
                              {charset}
                            </SelectItem>
                          ))}
                        </SelectContent>
                      </Select>
                    </FormField>
                    <FormField>
                      <Label htmlFor="resource-collation">Collation</Label>
                      <Combobox
                        id="resource-collation"
                        items={(service.charsets[form.data.charset]?.list ?? []).map((collation) => ({ value: collation, label: collation }))}
                        value={form.data.collation}
                        onValueChange={(collation) => form.setData('collation', collation)}
                        placeholder="Collation"
                        searchText="Search collations..."
                        noneFoundText="No collations found."
                      />
                    </FormField>
                  </div>
                )}
              </>
            )}
            <FormField>
              <Label htmlFor="resource-alias">
                Variable prefix <span className="text-muted-foreground font-normal">(optional)</span>
              </Label>
              <Input
                id="resource-alias"
                value={form.data.alias}
                maxLength={64}
                placeholder="ANALYTICS"
                disabled={form.processing}
                onChange={(e) => form.setData('alias', e.target.value.toUpperCase())}
                className="font-mono"
              />
              <p className="text-muted-foreground text-xs">Only needed for a second {redis ? 'Redis' : 'database'}, e.g. ANALYTICS_DB_HOST.</p>
            </FormField>
            <Alert>
              <TriangleAlertIcon className="text-warning!" />
              <AlertDescription>
                Components on other servers need a shared private network. Vito limits the {redis ? 'Redis' : 'database'} port to attached components,
                so other remote clients{redis ? '' : ', including Sites,'} may lose access. Enabling networking restarts the service
                {redis ? ', and local clients must then use its password' : ''}. The firewall must cover IPv4 and IPv6. Stored data is kept.
              </AlertDescription>
            </Alert>
            {Object.entries(form.errors).map(([key, message]) => (
              <InputError key={key} message={message} />
            ))}
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button form="attach-resource-form" type="submit" disabled={form.processing || incomplete}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}Add {redis ? 'Redis' : 'database'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
