import { ReactNode, useState, FormEventHandler, useEffect } from 'react';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { LoaderCircle, HelpCircle } from 'lucide-react';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { useForm, usePage } from '@inertiajs/react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/ui/input-error';
import type { SharedData } from '@/types';
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

type CreateSiteForm = {
  server: string;
  type: string;
  domain: string;
  php_version: string;
  source_control: string;
  repository: string;
  branch: string;
  user: string;
};

type IsolatedUserOption = { user: string; sites_count: number };

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
  const page = usePage<SharedData>();
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
    const reserved = page.props.configs.site.reserved_user_names ?? [];
    const blocked = new Set([...existing, ...reserved]);

    const suggestion = suggestIsolatedUsername(form.data.domain, blocked);
    if (suggestion !== form.data.user) form.setData('user', suggestion);
  }, [form.data.server, form.data.domain, userManuallyEdited, isolatedUsersQuery.data, isolatedUsersQuery.isLoading]);

  const submit: FormEventHandler = (e) => {
    e.preventDefault();
    form.post(route('sites.store', { server: form.data.server }), {
      onSuccess: () => {
        queryClient.invalidateQueries({ queryKey: ['isolated-users', parseInt(form.data.server, 10)] });
      },
    });
  };

  useEffect(() => {
    const typeConfig = page.props.configs.site.types[form.data.type];

    if (typeConfig?.form) {
      typeConfig.form.forEach((field: DynamicFieldConfig) => {
        if (field.default !== undefined) {
          /* @ts-expect-error dynamic types */
          if (form.data[field.name] === '' || form.data[field.name] === undefined) {
            /* @ts-expect-error dynamic types */
            form.setData(field.name, field.default);
          }
        }
      });
    }
  }, [form.data.type]);

  const getFormField = (field: DynamicFieldConfig) => {
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

    if (field.name === 'database') {
      return (
        <FormField key={`field-${field.name}`}>
          <Label htmlFor="database">Database</Label>
          <DatabaseSelect
            id="database"
            key={`field-${field.name}`}
            name="database"
            serverId={parseInt(form.data.server)}
            /*@ts-expect-error dynamic types*/
            value={form.data.database}
            /*@ts-expect-error dynamic types*/
            onValueChange={(value) => form.setData('database', value)}
            createWithUser={true}
            defaultCharset={field.componentProps?.defaultCharset as string | undefined}
            defaultCollation={field.componentProps?.defaultCollation as string | undefined}
          />
          {/*@ts-expect-error dynamic types*/}
          <InputError message={form.errors.database} />
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
            /*@ts-expect-error dynamic types*/
            value={form.data.database_user}
            /*@ts-expect-error dynamic types*/
            onValueChange={(value) => form.setData('database_user', value)}
            create={false}
          />
          {/*@ts-expect-error dynamic types*/}
          <InputError message={form.errors.database_user} />
        </FormField>
      );
    }

    return (
      <DynamicField
        key={`field-${field.name}`}
        /*@ts-expect-error dynamic types*/
        value={form.data[field.name]}
        /*@ts-expect-error dynamic types*/
        onChange={(value) => form.setData(field.name, value)}
        config={field}
        /*@ts-expect-error dynamic types*/
        error={form.errors[field.name]}
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
                        {Object.entries(page.props.configs.site.types).map(([key, type]) => (
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

                {page.props.configs.site.types[form.data.type].form?.map((config) => getFormField(config))}
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
