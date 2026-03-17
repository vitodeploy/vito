import ServerLayout from '@/layouts/server/layout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import { Application, SettingsFieldConfig } from '@/types/application';
import { SharedData } from '@/types';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import DateTime from '@/components/date-time';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { FormEvent, ReactNode, useEffect, useState } from 'react';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import {
  Sheet,
  SheetClose,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from '@/components/ui/sheet';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/ui/input-error';
import { LoaderCircle, LoaderCircleIcon } from 'lucide-react';
import FormSuccessful from '@/components/form-successful';

type Page = {
  server: Server;
  application: Application;
} & SharedData;

function DeleteApplication({ application, children }: { application: Application; children: ReactNode }) {
  const form = useForm({ domain: '' });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.delete(route('applications.destroy', { server: application.server_id, application: application.id }));
  };

  return (
    <Dialog>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete {application.domain}</DialogTitle>
          <DialogDescription className="sr-only">Delete application and its resources.</DialogDescription>
        </DialogHeader>
        <p className="p-4">
          Are you sure you want to delete <strong>{application.domain}</strong>? This action cannot be undone.
        </p>
        <Form id="delete-application-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="domain">Type the domain to confirm</Label>
              <Input id="domain" value={form.data.domain} onChange={(e) => form.setData('domain', e.target.value)} placeholder={application.domain} />
              <InputError message={form.errors.domain} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter className="gap-2">
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button form="delete-application-form" variant="destructive" disabled={form.processing || form.data.domain !== application.domain}>
            {form.processing && <LoaderCircleIcon className="size-4 animate-spin" />}
            Delete application
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function SettingRow({ label, last, children }: { label: string; last?: boolean; children: React.ReactNode }) {
  return (
    <>
      <div className="flex items-center justify-between p-4">
        <span>{label}</span>
        <span className="text-muted-foreground">{children}</span>
      </div>
      {!last && <Separator />}
    </>
  );
}

function InfoField({ field, application }: { field: SettingsFieldConfig; application: Application }) {
  let value: unknown;
  if (field.source === 'type_data') {
    value = application.type_data[field.key || field.name];
  } else {
    value = application[field.key || field.name];
  }

  if (field.format === 'boolean') {
    return <SettingRow label={field.label}>{value ? 'Enabled' : 'Disabled'}</SettingRow>;
  }

  if (field.format === 'badge') {
    const color = field.key === 'status' ? application.status_color : 'outline';
    return (
      <SettingRow label={field.label}>
        <Badge variant={color as 'outline'}>{String(value ?? '-')}</Badge>
      </SettingRow>
    );
  }

  if (field.format === 'link') {
    return (
      <SettingRow label={field.label}>
        <a href={application.url} target="_blank" className="hover:underline">
          {String(value ?? '-')}
        </a>
      </SettingRow>
    );
  }

  // array display
  if (Array.isArray(value)) {
    return <SettingRow label={field.label}>{value.length > 0 ? value.join(', ') : '-'}</SettingRow>;
  }

  return <SettingRow label={field.label}>{value != null ? String(value) : '-'}</SettingRow>;
}

function EditTemplate({ application, server }: { application: Application; server: Server }) {
  const [open, setOpen] = useState(false);
  const [loading, setLoading] = useState(false);
  const [loaded, setLoaded] = useState(false);

  const form = useForm({ template: '' });
  const resetForm = useForm();

  const routeParams = { server: server.id, application: application.id };

  useEffect(() => {
    if (open && !loaded) {
      setLoading(true);
      fetch(route('applications.template', routeParams), {
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content || '',
        },
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.template !== undefined) {
            form.setData('template', data.template);
          }
          setLoaded(true);
        })
        .finally(() => setLoading(false));
    }
  }, [open, loaded]);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.put(route('applications.template.update', routeParams), {
      onSuccess: () => setOpen(false),
    });
  };

  const handleReset = () => {
    resetForm.post(route('applications.template.reset', routeParams), {
      onSuccess: () => {
        setLoaded(false);
        setOpen(false);
      },
    });
  };

  return (
    <>
      <div className="flex items-center justify-between p-4">
        <span>Host Template</span>
        <Sheet open={open} onOpenChange={setOpen}>
          <SheetTrigger asChild>
            <Button variant="outline" className="h-6">
              Edit Template
            </Button>
          </SheetTrigger>
          <SheetContent className="flex w-full flex-col overflow-hidden lg:max-w-3xl">
            <SheetHeader>
              <SheetTitle>Edit Template</SheetTitle>
              <SheetDescription>Host Template</SheetDescription>
            </SheetHeader>
            {loading ? (
              <div className="flex items-center justify-center p-8">
                <LoaderCircle className="h-6 w-6 animate-spin" />
              </div>
            ) : (
              <Form id="edit-template-form" className="min-h-0 flex-1 overflow-y-auto p-4" onSubmit={submit}>
                <FormFields>
                  <FormField>
                    <Label htmlFor="template">Template</Label>
                    <Textarea
                      id="template"
                      value={form.data.template}
                      onChange={(e) => form.setData('template', e.target.value)}
                      className="min-h-[400px] max-h-[65vh] font-mono text-sm"
                    />
                    <InputError message={form.errors.template} />
                  </FormField>
                </FormFields>
              </Form>
            )}
            <SheetFooter>
              <div className="flex w-full items-center justify-between">
                <div>
                  <Button variant="outline" disabled={resetForm.processing} onClick={handleReset}>
                    {resetForm.processing && <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />}
                    Reset to Default
                  </Button>
                </div>
                <div className="flex items-center gap-2">
                  <SheetClose asChild>
                    <Button variant="outline">Cancel</Button>
                  </SheetClose>
                  <Button type="submit" form="edit-template-form" disabled={form.processing || loading}>
                    {form.processing && <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />}
                    <FormSuccessful successful={form.recentlySuccessful} />
                    Save & Deploy
                  </Button>
                </div>
              </div>
            </SheetFooter>
          </SheetContent>
        </Sheet>
      </div>
      <Separator />
    </>
  );
}

export default function ApplicationSettings() {
  const page = usePage<Page>();
  const { application, server } = page.props;
  const typeConfig = page.props.configs.application.types[application.type];
  const settingsFields = typeConfig?.settings_fields || [];

  return (
    <ServerLayout>
      <Head title={`Settings - ${application.domain}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Settings" description="Application details and configuration" />
        </HeaderContainer>

        <Card>
          <CardHeader>
            <CardTitle>Application details</CardTitle>
            <CardDescription>View application configuration</CardDescription>
          </CardHeader>
          <CardContent>
            <SettingRow label="ID">{application.id}</SettingRow>

            {settingsFields.map((field) => (
              <InfoField key={field.name} field={field} application={application} />
            ))}

            <EditTemplate application={application} server={server} />

            <div className="flex items-center justify-between p-4">
              <span>Created at</span>
              <span className="text-muted-foreground">
                <DateTime date={application.created_at} />
              </span>
            </div>
          </CardContent>
        </Card>

        <Card className="border-destructive/50">
          <CardHeader>
            <CardTitle>Delete application</CardTitle>
            <CardDescription>This will remove the application and its vhost configuration from the server.</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-2 p-4">
              <p>This action is irreversible and will delete all data associated with the application.</p>
              <DeleteApplication application={application}>
                <Button variant="destructive">Delete application</Button>
              </DeleteApplication>
            </div>
          </CardContent>
        </Card>
      </Container>
    </ServerLayout>
  );
}
