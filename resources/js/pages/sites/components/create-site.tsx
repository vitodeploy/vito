import { ReactNode, useState, FormEventHandler, useEffect, useMemo, useRef } from 'react';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { LoaderCircle, HelpCircle } from 'lucide-react';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { useForm } from '@inertiajs/react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/ui/input-error';
import { useConfigs } from '@/stores/bootstrap-store';
import SourceControlSelect from '@/pages/source-controls/components/source-control-select';
import { Server } from '@/types/server';
import ServerSelect from '@/pages/servers/components/server-select';
import ServiceVersionSelect from '@/pages/services/components/service-version-select';
import { DynamicFieldConfig } from '@/types/dynamic-field-config';
import DynamicField from '@/components/ui/dynamic-field';
import DatabaseSelect from '@/pages/databases/components/database-select';
import DatabaseUserSelect from '@/pages/database-users/components/database-user-select';
import IsolatedUserSelect from '@/pages/sites/components/isolated-user-select';
import SelectRepo from '@/pages/source-controls/components/select-repo';
import SelectBranch from '@/pages/source-controls/components/select-branch';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { IsolatedUserOption } from '@/types/isolated-user';

type CreateSiteForm = {
  server: string;
  type: string;
  domain: string;
  php_version: string;
  source_control: string;
  repository: string;
  branch: string;
  user: string;
  // Tooling versions land here as `{tool_id}_version` (e.g. `node_version`,
  // `bun_version`), driven by the site type's `createTimeTools()` and the
  // `tooling` DynamicField.
  [key: string]: string | number | boolean | string[] | undefined;
};

function suggestIsolatedUsername(domain: string, blocked: ReadonlySet<string>): string {
  if (!domain) return '';

  const slug = domain
    .toLowerCase()
    .replace(/^https?:\/\//, '')
    .replace(/^www\./, '')
    .replace(/[^a-z0-9]/g, '')
    .replace(/^\d+/, '');

  const base = slug.slice(0, 6);
  if (base.length === 0) return '';

  for (let i = 0; i < 1000; i++) {
    const candidate = i === 0 ? base : `${base}${i}`;
    if (candidate.length < 3 || candidate.length > 32) continue;
    if (blocked.has(candidate)) continue;
    return candidate;
  }

  return '';
}

export default function CreateSite({
  server,
  defaultOpen,
  onOpenChange,
  children,
}: {
  server?: Server;
  defaultOpen?: boolean;
  onOpenChange?: (open: boolean) => void;
  children: ReactNode;
}) {
  const configs = useConfigs()!;
  const queryClient = useQueryClient();
  const [open, setOpen] = useState(defaultOpen || false);
  const [userManuallyEdited, setUserManuallyEdited] = useState(false);

  useEffect(() => {
    if (defaultOpen !== undefined) {
      setOpen(defaultOpen);
    }
  }, [defaultOpen]);

  const handleOpenChange = (isOpen: boolean) => {
    setOpen(isOpen);
    if (onOpenChange) {
      onOpenChange(isOpen);
    }
  };

  const form = useForm<CreateSiteForm>({
    server: server?.id.toString() || '',
    type: 'laravel',
    domain: '',
    php_version: '',
    source_control: '',
    repository: '',
    branch: '',
    user: '',
  });

  const serverId = form.data.server ? parseInt(form.data.server, 10) : 0;
  const isolatedUsersQuery = useQuery<IsolatedUserOption[]>({
    queryKey: ['isolated-users', serverId],
    queryFn: async () => (await axios.get(route('sites.isolated-users', { server: serverId }))).data,
    enabled: !!serverId,
  });

  useEffect(() => {
    if (userManuallyEdited) return;

    if (!form.data.server) {
      if (form.data.user !== '') form.setData('user', '');
      return;
    }

    if (!form.data.domain) {
      if (form.data.user !== '') form.setData('user', '');
      return;
    }

    if (isolatedUsersQuery.isLoading) return;

    const existing = (isolatedUsersQuery.data ?? []).map((u) => u.user);
    const reserved = configs.site.reserved_user_names ?? [];
    const blocked = new Set([...existing, ...reserved]);

    const suggestion = suggestIsolatedUsername(form.data.domain, blocked);
    if (suggestion !== form.data.user) form.setData('user', suggestion);
  }, [form.data.server, form.data.domain, userManuallyEdited, isolatedUsersQuery.data, isolatedUsersQuery.isLoading, configs]);

  const submit: FormEventHandler = (e) => {
    e.preventDefault();
    form.post(route('sites.store', { server: form.data.server }), {
      onSuccess: () => {
        queryClient.invalidateQueries({ queryKey: ['isolated-users', parseInt(form.data.server, 10)] });
      },
    });
  };

  useEffect(() => {
    const typeConfig = configs.site.types[form.data.type];

    const sourceControlFields = ['source_control', 'repository', 'branch'];
    const sourceControlOff = typeConfig?.form?.some((f) => f.name === 'use_source_control') && !form.data.use_source_control;

    if (typeConfig?.form) {
      typeConfig.form.forEach((field: DynamicFieldConfig) => {
        if (sourceControlOff && sourceControlFields.includes(field.name)) {
          return;
        }
        if (field.default !== undefined) {
          if (form.data[field.name] === '' || form.data[field.name] === undefined) {
            form.setData(field.name, field.default);
          }
        }
      });
    }
  }, [form.data.type, form.data.use_source_control, form.setData, configs]);

  const selectedIsolatedUser = useMemo<IsolatedUserOption | null>(
    () => (isolatedUsersQuery.data ?? []).find((u) => u.user === form.data.user) ?? null,
    [isolatedUsersQuery.data, form.data.user],
  );

  const lockedVersions = selectedIsolatedUser?.runtime_versions ?? {};

  // Tool ids participating in the current site type's form, with their kind:
  //   - 'tooling'          → optional multi-tool field; unlock fallback = 'none'
  //   - 'tooling-picker'   → single required tool; unlock fallback = latest version
  //   - 'tooling-selector' → tool chosen from a list; treated like a picker for
  //                          lock-sync purposes (version required when no overlap)
  type ActiveTool = { toolId: string; kind: 'tooling' | 'tooling-picker' | 'tooling-selector' };
  const activeTools = useMemo<ActiveTool[]>(() => {
    const typeConfig = configs.site.types[form.data.type];
    const result: ActiveTool[] = [];
    for (const f of typeConfig?.form ?? []) {
      if (f.type !== 'tooling' && f.type !== 'tooling-picker' && f.type !== 'tooling-selector') continue;
      const raw = f.options;
      const ids = Array.isArray(raw) ? raw : raw ? Object.values(raw) : [];
      for (const id of ids) result.push({ toolId: id, kind: f.type });
    }
    return result;
  }, [configs, form.data.type]);

  // Tool ids that have a dedicated `toolingPicker` field on this form. Used by
  // the `toolingSelector` render to suppress the version companion when the
  // picked tool's version is already controlled by another field.
  const pickerToolIds = useMemo<Set<string>>(
    () => new Set(activeTools.filter((t) => t.kind === 'tooling-picker').map((t) => t.toolId)),
    [activeTools],
  );

  const previousLocksRef = useRef<Record<string, string | null>>({});

  useEffect(() => {
    const catalogue = configs.tooling ?? [];
    activeTools.forEach(({ toolId, kind }) => {
      const formKey = `${toolId}_version`;
      const locked = lockedVersions[toolId] ?? null;
      const current = (form.data as Record<string, unknown>)[formKey];

      if (locked) {
        if (current !== locked) form.setData(formKey, locked);
        previousLocksRef.current[toolId] = locked;
        return;
      }

      if (previousLocksRef.current[toolId]) {
        const fallback = kind === 'tooling' ? 'none' : (catalogue.find((t) => t.id === toolId)?.supported_versions[0] ?? '');
        if (current !== fallback) form.setData(formKey, fallback);
        previousLocksRef.current[toolId] = null;
      }
    });
  }, [activeTools, lockedVersions, form.data, form.setData, configs]);

  const hasSourceControlToggle = useMemo<boolean>(
    () => (configs.site.types[form.data.type]?.form ?? []).some((f) => f.name === 'use_source_control'),
    [configs, form.data.type],
  );
  const sourceControlEnabled = !hasSourceControlToggle || !!form.data.use_source_control;

  useEffect(() => {
    if (hasSourceControlToggle && !form.data.use_source_control) {
      if (form.data.source_control) form.setData('source_control', '');
      if (form.data.repository) form.setData('repository', '');
      if (form.data.branch) form.setData('branch', '');
    }
  }, [hasSourceControlToggle, form.data.use_source_control, form.data.source_control, form.data.repository, form.data.branch, form.setData]);

  const getFormField = (field: DynamicFieldConfig) => {
    if (!sourceControlEnabled && (field.name === 'source_control' || field.name === 'repository' || field.name === 'branch')) {
      return null;
    }

    if (field.name === 'source_control') {
      return (
        <FormField key={`field-${field.name}`}>
          <Label htmlFor="source_control">Source Control</Label>
          <SourceControlSelect
            id="source_control"
            value={form.data.source_control}
            onValueChange={(value) => form.setData('source_control', value)}
          />
          <InputError message={form.errors.source_control} />
        </FormField>
      );
    }

    if (field.name === 'repository') {
      return (
        <FormField key={`field-${field.name}`}>
          <Label htmlFor="repository">Repository</Label>
          <SelectRepo
            sourceControlId={form.data.source_control}
            value={form.data.repository}
            onValueChange={(value) => form.setData('repository', value)}
            placeholder="owner/repository"
          />
          <InputError message={form.errors.repository} />
        </FormField>
      );
    }

    if (field.name === 'branch') {
      return (
        <FormField key={`field-${field.name}`}>
          <Label htmlFor="branch">Branch</Label>
          <SelectBranch
            sourceControlId={form.data.source_control}
            repository={form.data.repository}
            value={form.data.branch}
            onValueChange={(value) => form.setData('branch', value)}
            placeholder="e.g. main, master, develop"
          />
          <InputError message={form.errors.branch} />
        </FormField>
      );
    }

    if (field.name === 'php_version') {
      return (
        <FormField key={`field-${field.name}`}>
          <Label htmlFor="php_version">PHP Version</Label>
          <ServiceVersionSelect
            id="php_version"
            serverId={parseInt(form.data.server)}
            service="php"
            value={form.data.php_version}
            onValueChange={(value) => form.setData('php_version', value)}
          />
          <InputError message={form.errors.php_version} />
        </FormField>
      );
    }

    if (field.type === 'tooling') {
      const rawOptions = field.options;
      const toolIds = Array.isArray(rawOptions) ? rawOptions : rawOptions ? Object.values(rawOptions) : [];
      const catalogue = configs.tooling ?? [];
      const showLaravelNotice = form.data.type === 'laravel' && toolIds.includes('node');

      return (
        <FormField key={`field-${field.name}`}>
          {showLaravelNotice && (
            <Alert role="status">
              <AlertDescription>Laravel sites typically need a JavaScript runtime to build front-end assets during deployment.</AlertDescription>
            </Alert>
          )}
          {field.label && <Label>{field.label}</Label>}
          <div className="flex flex-col gap-3">
            {toolIds.map((toolId) => {
              const descriptor = catalogue.find((t) => t.id === toolId);
              if (!descriptor) return null;
              const formKey = `${toolId}_version`;
              const locked = lockedVersions[toolId] ?? null;
              const options = ['none', ...descriptor.supported_versions];
              const labelFor = (v: string) => (v === 'none' ? 'None' : `${descriptor.label} ${v}`);
              const value = ((form.data as Record<string, unknown>)[formKey] as string | undefined) ?? 'none';

              return (
                <div key={toolId} className="space-y-2">
                  <Label htmlFor={`${toolId}-version`}>{descriptor.label}</Label>
                  {locked ? (
                    <>
                      <Alert role="status">
                        <AlertDescription className="block">
                          Isolated user <span className="font-medium">{form.data.user}</span> already has{' '}
                          <span className="font-medium">{labelFor(locked)}</span> installed; this can be modified via tooling against the site.
                        </AlertDescription>
                      </Alert>
                      <Select value={locked} disabled>
                        <SelectTrigger id={`${toolId}-version`}>
                          <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectGroup>
                            <SelectItem value={locked}>{labelFor(locked)}</SelectItem>
                          </SelectGroup>
                        </SelectContent>
                      </Select>
                    </>
                  ) : (
                    <Select value={value} onValueChange={(v) => form.setData(formKey, v)}>
                      <SelectTrigger id={`${toolId}-version`}>
                        <SelectValue placeholder={`Select ${descriptor.label} version`} />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectGroup>
                          {options.map((v) => (
                            <SelectItem key={v} value={v}>
                              {labelFor(v)}
                            </SelectItem>
                          ))}
                        </SelectGroup>
                      </SelectContent>
                    </Select>
                  )}
                  <InputError message={(form.errors as Record<string, string | undefined>)[formKey]} />
                </div>
              );
            })}
          </div>
        </FormField>
      );
    }

    if (field.type === 'tooling-picker') {
      const rawOptions = field.options;
      const toolIds = Array.isArray(rawOptions) ? rawOptions : rawOptions ? Object.values(rawOptions) : [];
      const toolId = toolIds[0];
      const descriptor = (configs.tooling ?? []).find((t) => t.id === toolId);
      if (!toolId || !descriptor) return null;

      const formKey = `${toolId}_version`;
      const locked = lockedVersions[toolId] ?? null;
      const labelFor = (v: string) => `${descriptor.label} ${v}`;
      const value = ((form.data as Record<string, unknown>)[formKey] as string | undefined) ?? descriptor.supported_versions[0] ?? '';

      return (
        <FormField key={`field-${field.name}`}>
          <Label htmlFor={`${toolId}-version`}>{field.label ?? `${descriptor.label} Version`}</Label>
          {locked ? (
            <>
              <Alert role="status">
                <AlertDescription className="block">
                  Isolated user <span className="font-medium">{form.data.user}</span> already has{' '}
                  <span className="font-medium">{labelFor(locked)}</span> installed; this can be modified via tooling against the site.
                </AlertDescription>
              </Alert>
              <Select value={locked} disabled>
                <SelectTrigger id={`${toolId}-version`}>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    <SelectItem value={locked}>{labelFor(locked)}</SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>
            </>
          ) : (
            <Select value={value} onValueChange={(v) => form.setData(formKey, v)}>
              <SelectTrigger id={`${toolId}-version`}>
                <SelectValue placeholder={`Select ${descriptor.label} version`} />
              </SelectTrigger>
              <SelectContent>
                <SelectGroup>
                  {descriptor.supported_versions.map((v) => (
                    <SelectItem key={v} value={v}>
                      {labelFor(v)}
                    </SelectItem>
                  ))}
                </SelectGroup>
              </SelectContent>
            </Select>
          )}
          <InputError message={(form.errors as Record<string, string | undefined>)[formKey]} />
        </FormField>
      );
    }

    if (field.type === 'tooling-selector') {
      const rawOptions = field.options;
      const toolIds = Array.isArray(rawOptions) ? rawOptions : rawOptions ? Object.values(rawOptions) : [];
      const catalogue = configs.tooling ?? [];
      const pickedToolId = ((form.data as Record<string, unknown>)[field.name] as string | undefined) ?? toolIds[0] ?? '';
      const pickedDescriptor = catalogue.find((t) => t.id === pickedToolId);
      const versionInherited = pickerToolIds.has(pickedToolId);
      const versionKey = `${pickedToolId}_version`;
      const lockedVersion = lockedVersions[pickedToolId] ?? null;
      const versionValue =
        ((form.data as Record<string, unknown>)[versionKey] as string | undefined) ?? pickedDescriptor?.supported_versions[0] ?? '';
      const labelFor = (v: string) => (pickedDescriptor ? `${pickedDescriptor.label} ${v}` : v);

      return (
        <FormField key={`field-${field.name}`}>
          {field.label && <Label htmlFor={field.name}>{field.label}</Label>}
          <Select
            value={pickedToolId}
            onValueChange={(v) => {
              form.setData(field.name, v);
              if (pickerToolIds.has(v)) return;
              const nextDescriptor = catalogue.find((t) => t.id === v);
              const nextVersion = lockedVersions[v] ?? nextDescriptor?.supported_versions[0] ?? '';
              if (nextVersion) form.setData(`${v}_version`, nextVersion);
            }}
          >
            <SelectTrigger id={field.name}>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectGroup>
                {toolIds.map((id) => {
                  const d = catalogue.find((t) => t.id === id);
                  return (
                    <SelectItem key={id} value={id}>
                      {field.optionLabels?.[id] ?? d?.label ?? id}
                    </SelectItem>
                  );
                })}
              </SelectGroup>
            </SelectContent>
          </Select>
          <InputError message={(form.errors as Record<string, string | undefined>)[field.name]} />

          {!versionInherited && pickedDescriptor && (
            <div className="mt-3 space-y-2">
              <Label htmlFor={`${pickedToolId}-selector-version`}>{pickedDescriptor.label} Version</Label>
              {lockedVersion ? (
                <>
                  <Alert role="status">
                    <AlertDescription className="block">
                      Isolated user <span className="font-medium">{form.data.user}</span> already has{' '}
                      <span className="font-medium">{labelFor(lockedVersion)}</span> installed; this can be modified via tooling against the site.
                    </AlertDescription>
                  </Alert>
                  <Select value={lockedVersion} disabled>
                    <SelectTrigger id={`${pickedToolId}-selector-version`}>
                      <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectItem value={lockedVersion}>{labelFor(lockedVersion)}</SelectItem>
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                </>
              ) : (
                <Select key={pickedToolId} value={versionValue} onValueChange={(v) => form.setData(versionKey, v)}>
                  <SelectTrigger id={`${pickedToolId}-selector-version`}>
                    <SelectValue placeholder={`Select ${pickedDescriptor.label} version`} />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectGroup>
                      {pickedDescriptor.supported_versions.map((v) => (
                        <SelectItem key={v} value={v}>
                          {labelFor(v)}
                        </SelectItem>
                      ))}
                    </SelectGroup>
                  </SelectContent>
                </Select>
              )}
              <InputError message={(form.errors as Record<string, string | undefined>)[versionKey]} />
            </div>
          )}
        </FormField>
      );
    }

    if (field.name === 'database') {
      return (
        <FormField key={`field-${field.name}`}>
          <Label htmlFor="database">Database</Label>
          <DatabaseSelect
            id="database"
            key={`field-${field.name}`}
            name="database"
            serverId={parseInt(form.data.server)}
            value={form.data.database as string}
            onValueChange={(value) => form.setData('database', value)}
            createWithUser={true}
            defaultCharset={field.componentProps?.defaultCharset as string | undefined}
            defaultCollation={field.componentProps?.defaultCollation as string | undefined}
          />
          <InputError message={form.errors.database as string | undefined} />
        </FormField>
      );
    }

    if (field.name === 'database_user') {
      return (
        <FormField key={`field-${field.name}`}>
          <Label htmlFor="database-user">Database user</Label>
          <DatabaseUserSelect
            id="database-user"
            key={`field-${field.name}`}
            name="database_user"
            serverId={parseInt(form.data.server)}
            value={form.data.database_user as string}
            onValueChange={(value) => form.setData('database_user', value)}
            create={false}
          />
          <InputError message={form.errors.database_user as string | undefined} />
        </FormField>
      );
    }

    return (
      <DynamicField
        key={`field-${field.name}`}
        value={form.data[field.name] as string | number | boolean | string[] | undefined}
        onChange={(value) => form.setData(field.name, value)}
        config={field}
        error={(form.errors as Record<string, string | undefined>)[field.name]}
      />
    );
  };

  return (
    <Sheet open={open} onOpenChange={handleOpenChange}>
      <SheetTrigger asChild>{children}</SheetTrigger>
      <SheetContent className="w-full lg:max-w-3xl">
        <SheetHeader>
          <SheetTitle>Create site</SheetTitle>
          <SheetDescription>Fill in the details to create a new site.</SheetDescription>
        </SheetHeader>
        <Form id="create-site-form" className="p-4" onSubmit={submit}>
          <FormFields>
            {server === undefined && (
              <FormField>
                <Label htmlFor="server">Server</Label>
                <ServerSelect value={form.data.server} onValueChange={(value) => form.setData('server', value ? value.id.toString() : '')} />
                <InputError message={form.errors.server} />
              </FormField>
            )}

            {form.data.server && (
              <>
                <FormField>
                  <Label htmlFor="type">Site Type</Label>
                  <Select value={form.data.type} onValueChange={(value) => form.setData('type', value)}>
                    <SelectTrigger id="type">
                      <SelectValue placeholder="Select site type" />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        {Object.entries(configs.site.types).map(([key, type]) => (
                          <SelectItem key={`type-${key}`} value={key}>
                            {type.label}
                          </SelectItem>
                        ))}
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  <InputError message={form.errors.type} />
                </FormField>

                <FormField>
                  <Label htmlFor="domain">Domain</Label>
                  <Input
                    id="domain"
                    type="text"
                    value={form.data.domain}
                    onChange={(e) => form.setData('domain', e.target.value)}
                    placeholder="vitodeploy.com"
                  />
                  <InputError message={form.errors.domain} />
                </FormField>

                <FormField>
                  <Label htmlFor="user" className="flex items-center gap-1">
                    Isolated User
                    <Dialog>
                      <TooltipProvider>
                        <Tooltip>
                          <TooltipTrigger asChild>
                            <DialogTrigger asChild>
                              <button type="button" tabIndex={-1} className="text-muted-foreground hover:text-foreground">
                                <HelpCircle className="h-4 w-4" />
                              </button>
                            </DialogTrigger>
                          </TooltipTrigger>
                          <TooltipContent>Why?</TooltipContent>
                        </Tooltip>
                      </TooltipProvider>
                      <DialogContent>
                        <DialogHeader>
                          <DialogTitle>Why Isolated Users?</DialogTitle>
                          <DialogDescription>
                            Isolated users are mandatory to ensure security for your sites. If a site has security vulnerabilities and gets
                            compromised, the attacker cannot take full control of the server because the site runs under its own isolated user with
                            limited permissions.
                          </DialogDescription>
                        </DialogHeader>
                      </DialogContent>
                    </Dialog>
                  </Label>
                  <IsolatedUserSelect
                    serverId={parseInt(form.data.server, 10)}
                    value={form.data.user}
                    onValueChange={(value) => {
                      setUserManuallyEdited(true);
                      form.setData('user', value);
                    }}
                    onSearchChange={() => setUserManuallyEdited(true)}
                  />
                  <p className="text-muted-foreground text-xs">
                    Pick an existing isolated user to host this site alongside others, or create a new one.
                  </p>
                  <InputError message={form.errors.user} />
                </FormField>

                {configs.site.types[form.data.type].form?.map((config) => getFormField(config))}
              </>
            )}
          </FormFields>
        </Form>
        <SheetFooter>
          <div className="flex items-center gap-2">
            <Button type="submit" form="create-site-form" disabled={form.processing || !form.data.server}>
              {form.processing && <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />} Create
            </Button>
            <SheetClose asChild>
              <Button variant="outline" disabled={form.processing}>
                Cancel
              </Button>
            </SheetClose>
          </div>
        </SheetFooter>
      </SheetContent>
    </Sheet>
  );
}
