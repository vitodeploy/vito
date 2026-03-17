import { ReactNode, useState, FormEventHandler, useEffect } from 'react';
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
import { Server } from '@/types/server';
import { DynamicFieldConfig } from '@/types/dynamic-field-config';
import DynamicField from '@/components/ui/dynamic-field';
import { TagsInput } from '@/components/ui/tags-input';

type CreateApplicationForm = {
  type: string;
  domain: string;
  aliases: string[];
  [key: string]: unknown;
};

export default function CreateApplication({ server, children }: { server: Server; children: ReactNode }) {
  const page = usePage<SharedData>();
  const [open, setOpen] = useState(false);

  const form = useForm<CreateApplicationForm>({
    type: Object.keys(page.props.configs.application.types)[0] || '',
    domain: '',
    aliases: [],
  });

  const submit: FormEventHandler = (e) => {
    e.preventDefault();
    form.post(route('applications.store', { server: server.id }), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };

  useEffect(() => {
    const typeConfig = page.props.configs.application.types[form.data.type];

    if (typeConfig?.form) {
      typeConfig.form.forEach((field: DynamicFieldConfig) => {
        if (field.default !== undefined) {
          if (form.data[field.name] === '' || form.data[field.name] === undefined) {
            form.setData(field.name as keyof CreateApplicationForm, field.default);
          }
        }
      });
    }
  }, [form.data.type]);

  return (
    <Sheet open={open} onOpenChange={setOpen}>
      <SheetTrigger asChild>{children}</SheetTrigger>
      <SheetContent className="w-full lg:max-w-3xl">
        <SheetHeader>
          <SheetTitle>Create application</SheetTitle>
          <SheetDescription>Fill in the details to create a new application.</SheetDescription>
        </SheetHeader>
        <Form id="create-application-form" className="p-4" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="type">Application Type</Label>
              <Select value={form.data.type} onValueChange={(value) => form.setData('type', value)}>
                <SelectTrigger id="type">
                  <SelectValue placeholder="Select type" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    {Object.entries(page.props.configs.application.types).map(([key, type]) => (
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
                placeholder="proxy.example.com"
              />
              <InputError message={form.errors.domain} />
            </FormField>

            <FormField>
              <Label htmlFor="aliases">Aliases</Label>
              <TagsInput id="aliases" type="text" value={form.data.aliases} placeholder="Add aliases" onValueChange={(value) => form.setData('aliases', value)} />
              <p className="text-muted-foreground text-xs">Press enter or comma to add an alias and press backspace to remove the last alias.</p>
              <InputError message={form.errors.aliases} />
              {Object.keys(form.errors)
                .filter((key) => key.startsWith('aliases.'))
                .map((key) => (
                  <InputError key={key} message={form.errors[key as keyof typeof form.errors] as string} />
                ))}
            </FormField>

            {page.props.configs.application.types[form.data.type]?.form?.map((config) => (
              <DynamicField
                key={`field-${config.name}`}
                value={form.data[config.name] as string}
                onChange={(value) => form.setData(config.name as keyof CreateApplicationForm, value)}
                config={config}
                error={form.errors[config.name as keyof typeof form.errors] as string}
              />
            ))}
          </FormFields>
        </Form>
        <SheetFooter>
          <div className="flex items-center gap-2">
            <Button type="submit" form="create-application-form" disabled={form.processing}>
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
