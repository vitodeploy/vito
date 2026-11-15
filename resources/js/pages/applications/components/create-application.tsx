import { FormEvent, useEffect, useRef, useState } from 'react';
import { Link, useForm } from '@inertiajs/react';
import * as ToggleGroup from '@radix-ui/react-toggle-group';
import { LoaderCircleIcon } from 'lucide-react';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { DialogControlProps } from '@/components/dialogs/registry';
import DynamicField from '@/components/ui/dynamic-field';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/ui/input-error';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import SelectBranch from '@/pages/source-controls/components/select-branch';
import { useConfigs } from '@/stores/bootstrap-store';
import { SourceControl } from '@/types/source-control';
import { CapabilityConfiguration, ComponentOptions, EnvironmentFormData, RuntimePreset } from '@/types/application';
import ApplicationFields, { ApplicationFormData, usableSourceControls } from './application-fields';
import CapabilityFields from './capability-fields';
import EnvironmentFields, { environmentDefaults } from './environment-fields';
import { Advanced, Field, SwitchField } from './section';

type Server = ComponentOptions['servers'][number];
type Detected = { branch: string | null; runtime: string | null; versions: string[] };
type CreateApplicationForm = ApplicationFormData & {
  runtime: string;
  environment: EnvironmentFormData;
  server_id: string;
  user: string;
  capabilities: CapabilityConfiguration[];
  database: boolean;
  auto_deploy: boolean;
};

const webCapability = (preset?: RuntimePreset): CapabilityConfiguration[] => {
  const web = preset?.capabilities.find((capability) => capability.id === 'web');
  return web ? [{ capability: web.id, config: Object.fromEntries(web.form.map((field) => [field.name, field.default ?? ''])) }] : [];
};

const installedVersions = (preset?: RuntimePreset, server?: Server): string[] =>
  preset?.version_service ? (server?.services[preset.version_service] ?? []) : (preset?.versions ?? []);

const pickVersion = (preset: RuntimePreset | undefined, server: Server | undefined, detected: string[]): string => {
  const candidates = detected.length ? detected : (preset?.versions ?? []);
  const installed = installedVersions(preset, server);
  return candidates.find((version) => installed.includes(version)) ?? candidates[0] ?? '';
};

export default function CreateApplication({
  open,
  onOpenChange,
  sourceControls,
  servers,
}: DialogControlProps & { sourceControls: SourceControl[]; servers: ComponentOptions['servers'] }) {
  const configs = useConfigs()!;
  const runtimes = configs.application.runtimes;
  const available = usableSourceControls(sourceControls, configs);
  const [detecting, setDetecting] = useState(false);
  const [detected, setDetected] = useState<Detected | null>(null);
  const [changingRuntime, setChangingRuntime] = useState(false);
  const [typedBranch, setTypedBranch] = useState(false);
  const runtimeChosen = useRef(false);
  const versionChosen = useRef(false);
  const databaseChosen = useRef(false);
  const detectedKey = useRef('');
  const form = useForm<CreateApplicationForm>({
    name: '',
    repository: '',
    source_control_id: available.length === 1 ? String(available[0].id) : '',
    runtime: '',
    environment: { ...environmentDefaults(), branch: '' },
    server_id: servers.length === 1 ? String(servers[0].id) : '',
    user: '',
    capabilities: [],
    database: false,
    auto_deploy: true,
  });
  const { setData, clearErrors } = form;
  const { source_control_id: sourceControlId, repository } = form.data;
  const branch = form.data.environment.branch;
  const preset = runtimes.find((runtime) => runtime.id === form.data.runtime);
  const server = servers.find((item) => String(item.id) === form.data.server_id);
  const detectedVersions = detected?.runtime === form.data.runtime ? detected.versions : [];
  const detectedList = detectedVersions.join(',');
  const version = form.data.environment.runtime_version;
  const environmentErrors = Object.fromEntries(
    Object.entries(form.errors)
      .filter(([key]) => key.startsWith('environment.'))
      .map(([key, value]) => [key.slice('environment.'.length), value]),
  );
  const servesWeb = form.data.capabilities.some((capability) => capability.capability === 'web');
  const missingServices = server && preset && servesWeb ? preset.required_services.filter((type) => !server.services[type]?.length) : [];
  const missingVersion = !!server && !!preset?.version_service && !!version && !installedVersions(preset, server).includes(version);
  const blocked = missingServices.length > 0 || missingVersion;

  useEffect(() => {
    const key = [sourceControlId, repository, branch].join('|');
    if (!sourceControlId || !repository) {
      detectedKey.current = '';
      setDetected(null);
      return;
    }
    if (key === detectedKey.current) return;

    const controller = new AbortController();
    const fallBackToPicker = () => {
      if (!runtimeChosen.current) {
        setDetected(null);
        setData((data) => ({ ...data, runtime: '', capabilities: [] }));
      }
      setChangingRuntime(true);
    };
    const timer = setTimeout(async () => {
      setDetecting(true);
      try {
        const query = new URLSearchParams({ source_control_id: sourceControlId, repository, ...(branch ? { branch } : {}) });
        const response = await fetch(`/applications/detect?${query}`, { headers: { Accept: 'application/json' }, signal: controller.signal });
        if (!response.ok) {
          fallBackToPicker();
          return;
        }
        const result = (await response.json()) as Detected;
        const resolvedBranch = branch || result.branch || '';
        detectedKey.current = [sourceControlId, repository, resolvedBranch].join('|');
        setDetected(result);
        const nextRuntime = runtimes.find((runtime) => runtime.id === result.runtime);
        setData((data) => {
          const switching = !runtimeChosen.current && !!nextRuntime && data.runtime !== nextRuntime.id;
          return {
            ...data,
            environment: {
              ...data.environment,
              ...(switching ? environmentDefaults(nextRuntime) : {}),
              ...(switching && versionChosen.current && nextRuntime.versions.includes(data.environment.runtime_version)
                ? { runtime_version: data.environment.runtime_version }
                : {}),
              name: data.environment.name,
              branch: resolvedBranch,
            },
            ...(switching ? { runtime: nextRuntime.id, capabilities: webCapability(nextRuntime) } : {}),
            ...(!nextRuntime && !runtimeChosen.current ? { runtime: '', capabilities: [] } : {}),
          };
        });
        if (!runtimeChosen.current) setChangingRuntime(!nextRuntime);
      } catch {
        if (!controller.signal.aborted) fallBackToPicker();
      } finally {
        setDetecting(false);
      }
    }, 300);

    return () => {
      clearTimeout(timer);
      controller.abort();
      setDetecting(false);
    };
  }, [sourceControlId, repository, branch, runtimes, setData]);

  useEffect(() => {
    const nextVersion = pickVersion(preset, server, detectedList ? detectedList.split(',') : []);
    setData((data) => ({
      ...data,
      environment: versionChosen.current ? data.environment : { ...data.environment, runtime_version: nextVersion },
      database: databaseChosen.current ? data.database : !!preset?.suggests_database,
    }));
  }, [preset, server, detectedList, setData]);

  const change = (name: keyof ApplicationFormData, value: string) => {
    const repositoryChanged = (name === 'repository' || name === 'source_control_id') && value !== form.data[name];
    if (repositoryChanged) {
      runtimeChosen.current = false;
      versionChosen.current = false;
      setDetected(null);
      setChangingRuntime(false);
    }
    form.setData((data) => {
      const derived = data.repository.split('/').pop() ?? '';
      return {
        ...data,
        [name]: value,
        ...(name === 'repository' && (!data.name || data.name === derived) ? { name: value.split('/').pop() ?? '' } : {}),
        ...(repositoryChanged
          ? { runtime: '', capabilities: [], environment: { ...environmentDefaults(), name: data.environment.name, branch: '' } }
          : {}),
      };
    });
  };
  const changeBranch = (value: string) => form.setData((data) => ({ ...data, environment: { ...data.environment, branch: value } }));
  const selectRuntime = (runtime: string) => {
    const next = runtimes.find((item) => item.id === runtime);
    runtimeChosen.current = true;
    versionChosen.current = false;
    form.setData((data) => ({
      ...data,
      runtime,
      capabilities: webCapability(next),
      environment: { ...environmentDefaults(next), name: data.environment.name, branch: data.environment.branch },
    }));
    clearErrors('runtime', 'environment.runtime_version');
  };
  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.transform((data) => ({ ...data, database: data.database && !!server?.database }));
    form.post('/applications', { onSuccess: () => onOpenChange(false) });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>New application</DialogTitle>
          <DialogDescription>Pick a repository and a server. Vito sets it up and deploys it.</DialogDescription>
        </DialogHeader>
        <Form id="create-application-form" className="p-4" onSubmit={submit}>
          <FormFields>
            <ApplicationFields picker data={form.data} errors={form.errors} onChange={change} sourceControls={sourceControls} />
            <Field
              id="branch"
              label="Branch"
              error={environmentErrors.branch}
              description={
                !typedBranch && (
                  <button type="button" className="underline" onClick={() => setTypedBranch(true)}>
                    Not listed? Type it instead
                  </button>
                )
              }
            >
              {typedBranch ? (
                <Input id="branch" className="font-mono" value={branch} placeholder="main" onChange={(e) => changeBranch(e.target.value)} />
              ) : (
                <SelectBranch
                  sourceControlId={sourceControlId}
                  repository={repository}
                  value={branch}
                  onValueChange={changeBranch}
                  placeholder="main"
                />
              )}
            </Field>
            <Field
              id="application-server"
              label="Server"
              error={form.errors.server_id}
              description={
                servers.length === 0 && (
                  <>
                    No ready servers yet.{' '}
                    <Link href="/servers" className="underline">
                      Add a server
                    </Link>{' '}
                    to deploy, or create the application now and place it later.
                  </>
                )
              }
            >
              <Select value={form.data.server_id} onValueChange={(value) => form.setData('server_id', value)}>
                <SelectTrigger id="application-server">
                  <SelectValue placeholder="Select a server" />
                </SelectTrigger>
                <SelectContent>
                  {servers.map((item) => (
                    <SelectItem key={item.id} value={String(item.id)}>
                      {item.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {blocked && server && (
                <p role="alert" className="text-destructive text-xs">
                  {missingServices.length > 0
                    ? `${server.name} needs: ${missingServices.join(', ')}. `
                    : `${preset?.label} ${version} isn't installed on ${server.name}. Choose another version under Advanced, or `}
                  <Link href={`/servers/${server.id}/services`} className="underline">
                    {missingServices.length > 0 ? 'Install on the server' : 'install it on the server'}
                  </Link>
                  .
                </p>
              )}
            </Field>
            <FormField>
              <Label id="application-runtime">Runtime</Label>
              {detecting ? (
                <p role="status" className="text-muted-foreground flex items-center gap-2 text-sm">
                  <LoaderCircleIcon className="size-4 animate-spin" />
                  Looking at the repository...
                </p>
              ) : preset && !changingRuntime ? (
                <div className="flex min-h-9 items-center justify-between gap-2 text-sm">
                  <span>
                    {detected?.runtime === preset.id && !runtimeChosen.current && <span className="text-muted-foreground">Detected </span>}
                    <span className="font-medium">{preset.label}</span>
                    {version
                      ? ` · ${preset.version_service && preset.version_service !== preset.id ? `${preset.version_service.toUpperCase()} ` : ''}${version}`
                      : ''}
                  </span>
                  <Button type="button" variant="ghost" size="sm" onClick={() => setChangingRuntime(true)}>
                    Change
                  </Button>
                </div>
              ) : repository || changingRuntime ? (
                <ToggleGroup.Root
                  type="single"
                  aria-labelledby="application-runtime"
                  value={form.data.runtime}
                  onValueChange={(runtime) => runtime && selectRuntime(runtime)}
                  className="grid grid-cols-2 gap-2 sm:grid-cols-3"
                >
                  {runtimes.map((runtime) => (
                    <ToggleGroup.Item
                      key={runtime.id}
                      value={runtime.id}
                      className="hover:bg-accent focus-visible:ring-ring/50 data-[state=on]:border-primary data-[state=on]:bg-accent data-[state=on]:ring-primary/30 rounded-lg border px-3 py-2.5 text-left text-sm font-medium transition-colors outline-none focus-visible:ring-[3px] data-[state=on]:ring-2"
                    >
                      {runtime.label}
                    </ToggleGroup.Item>
                  ))}
                </ToggleGroup.Root>
              ) : (
                <p className="text-muted-foreground text-sm">Detected from the repository.</p>
              )}
              <p className="text-muted-foreground text-xs">The runtime can't be changed later.</p>
              <InputError message={form.errors.runtime} />
            </FormField>
            {server?.database ? (
              <SwitchField
                id="application-database"
                label={`Create a ${configs.service.services[server.database]?.label ?? server.database} database on ${server.name}`}
                description="Its credentials are added automatically. Vito limits the database port to attached components, so other remote clients lose access unless a firewall rule allows them."
                checked={form.data.database}
                onCheckedChange={(checked) => {
                  databaseChosen.current = true;
                  form.setData('database', checked);
                }}
                error={form.errors.database}
              />
            ) : (
              server &&
              preset?.suggests_database && (
                <p className="text-muted-foreground text-xs">
                  To create a database here, install a database service and a firewall on {server.name}. You can also add one later.
                </p>
              )
            )}
            <Advanced
              forceOpen={Object.keys(form.errors).some(
                (key) =>
                  key === 'name' ||
                  key === 'user' ||
                  key === 'auto_deploy' ||
                  key.startsWith('capabilities') ||
                  (key.startsWith('environment.') && key !== 'environment.branch'),
              )}
            >
              <Field id="application-name" label="Name" error={form.errors.name}>
                <Input id="application-name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
              </Field>
              <EnvironmentFields
                hideBranch
                preset={preset}
                data={form.data.environment}
                errors={environmentErrors}
                onChange={(name, value) => {
                  if (name === 'runtime_version') versionChosen.current = true;
                  form.setData((data) => ({ ...data, environment: { ...data.environment, [name]: value } }));
                }}
              />
              {(preset?.creation_form ?? []).map((field) => (
                <DynamicField
                  controlled
                  key={field.name}
                  config={field}
                  value={form.data.environment[field.name]}
                  onChange={(value) => form.setData((data) => ({ ...data, environment: { ...data.environment, [field.name]: value } }))}
                  error={environmentErrors[field.name]}
                />
              ))}
              {server && (
                <Field
                  id="application-user"
                  label="Run as"
                  description="An isolated user without sudo access. Leave empty to name it after the application."
                  error={form.errors.user}
                >
                  <Input
                    id="application-user"
                    value={form.data.user}
                    onChange={(e) => form.setData('user', e.target.value)}
                    autoComplete="off"
                    className="font-mono"
                  />
                </Field>
              )}
              {server && preset && (
                <FormField>
                  <Label>Capabilities</Label>
                  <div className="divide-y rounded-lg border">
                    <CapabilityFields
                      options={preset.capabilities}
                      value={form.data.capabilities}
                      onChange={(value) => form.setData('capabilities', value)}
                      errors={form.errors}
                    />
                  </div>
                </FormField>
              )}
              {server && (
                <SwitchField
                  id="application-auto-deploy"
                  label="Deploy on push"
                  description={`Deploys automatically when ${branch || 'the branch'} changes.`}
                  checked={form.data.auto_deploy}
                  onCheckedChange={(checked) => form.setData('auto_deploy', checked)}
                  error={form.errors.auto_deploy}
                />
              )}
            </Advanced>
            <InputError message={form.errors.environment} />
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button
            form="create-application-form"
            type="submit"
            disabled={form.processing || detecting || !form.data.runtime || !repository || !branch || blocked || (servers.length > 0 && !server)}
          >
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            {servers.length > 0 ? 'Deploy' : 'Create application'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
