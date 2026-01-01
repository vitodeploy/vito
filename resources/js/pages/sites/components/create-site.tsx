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
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/ui/input-error';
import type { SharedData } from '@/types';
import SourceControlSelect from '@/pages/source-controls/components/source-control-select';
import { Server } from '@/types/server';
import ServerSelect from '@/pages/servers/components/server-select';
import ServiceVersionSelect from '@/pages/services/components/service-version-select';
import { DynamicFieldConfig } from '@/types/dynamic-field-config';
import DynamicField from '@/components/ui/dynamic-field';
import { TagsInput } from '@/components/ui/tags-input';
import DatabaseSelect from '@/pages/databases/components/database-select';
import DatabaseUserSelect from '@/pages/database-users/components/database-user-select';
import SelectRepo from '@/pages/source-controls/components/select-repo';
import SelectBranch from '@/pages/source-controls/components/select-branch';

type CreateSiteForm = {
  server: string;
  type: string;
  domain: string;
  aliases: string[];
  php_version: string;
  source_control: string;
  repository: string;
  branch: string;
  user: string;
};

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
  const [open, setOpen] = useState(defaultOpen || false);

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
    type: 'php',
    domain: '',
    aliases: [],
    php_version: '',
    source_control: '',
    repository: '',
    branch: '',
    user: '',
  });

  const submit: FormEventHandler = (e) => {
    e.preventDefault();
    form.post(route('sites.store', { server: form.data.server }));
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
                  <Label htmlFor="aliases">Aliases</Label>
                  <TagsInput
                    id="aliases"
                    type="text"
                    value={form.data.aliases}
                    placeholder="Add aliases"
                    onValueChange={(value) => form.setData('aliases', value)}
                  />
                  <p className="text-muted-foreground text-xs">Press enter or comma to add an alias and press backspace to remove the last alias.</p>
                  <InputError message={form.errors.aliases} />
                  {Object.keys(form.errors)
                    .filter((key) => key.startsWith('aliases.'))
                    .map((key) => (
                      <InputError key={key} message={form.errors[key as keyof typeof form.errors] as string} />
                    ))}
                </FormField>

                <FormField>
                  <Label htmlFor="user" className="flex items-center gap-1">
                    Isolated User
                    <Dialog>
                      <TooltipProvider>
                        <Tooltip>
                          <TooltipTrigger asChild>
                            <DialogTrigger asChild>
                              <button type="button" className="text-muted-foreground hover:text-foreground">
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
                  <Input
                    id="user"
                    type="text"
                    value={form.data.user}
                    onChange={(e) => form.setData('user', e.target.value)}
                    placeholder="e.g. mysite"
                  />
                  <p className="text-muted-foreground text-xs">The isolated user for the site. Must be unique on the server.</p>
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
