import { ReactNode, useState, FormEventHandler } from 'react';
import { Sheet, SheetClose, SheetContent, SheetDescription, SheetFooter, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { LoaderCircle } from 'lucide-react';
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

type CreateSiteForm = {
  server: string;
  type: string;
  domain: string;
  aliases: string[];
  php_version: string;
  source_control: string;
  user: string;
};

export default function CreateSite({ server, children }: { server?: Server; children: ReactNode }) {
  const page = usePage<SharedData>();
  const [open, setOpen] = useState(false);

  const form = useForm<CreateSiteForm>({
    server: server?.id.toString() || '',
    type: 'php',
    domain: '',
    aliases: [],
    php_version: '',
    source_control: '',
    user: '',
  });

  const submit: FormEventHandler = (e) => {
    e.preventDefault();
    form.post(route('sites.store', { server: form.data.server }));
  };

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
    <Sheet open={open} onOpenChange={setOpen}>
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
                  <InputError message={form.errors.aliases} />
                </FormField>

                {page.props.configs.site.types[form.data.type].form?.map((config) => getFormField(config))}

                <FormField>
                  <Label htmlFor="user">Isolated User (Optional)</Label>
                  <Input
                    id="user"
                    type="text"
                    value={form.data.user}
                    onChange={(e) => form.setData('user', e.target.value)}
                    placeholder="Leave empty for using server's default user"
                  />
                  <InputError message={form.errors.user} />
                </FormField>
              </>
            )}
          </FormFields>
        </Form>
        <SheetFooter>
          <div className="flex items-center gap-2">
            <Button type="submit" form="create-site-form" disabled={form.processing}>
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
