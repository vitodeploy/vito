import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { Form, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import FormSuccessful from '@/components/form-successful';
import { LoaderCircleIcon } from 'lucide-react';
import { Application } from '@/types/application';
import { SourceControl } from '@/types/source-control';
import ApplicationFields from './application-fields';

export default function Settings({ application, sourceControls }: { application: Application; sourceControls: SourceControl[] }) {
  const form = useForm({
    name: application.name,
    repository: application.repository,
    source_control_id: application.source_control_id ? String(application.source_control_id) : '',
  });
  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.put(`/applications/${application.id}`, { preserveScroll: true });
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle>Application settings</CardTitle>
        <CardDescription>Repository changes immediately re-register component deploy keys. The running release changes on the next deploy. The runtime preset cannot be changed.</CardDescription>
      </CardHeader>
      <CardContent className="p-4">
        <Form id="application-settings-form" onSubmit={submit}>
          <FormFields>
            <ApplicationFields data={form.data} errors={form.errors} onChange={(name, value) => form.setData(name, value)} sourceControls={sourceControls} />
          </FormFields>
          <div className="flex items-center gap-2">
            <Button type="submit" disabled={form.processing}>
              {form.processing && <LoaderCircleIcon className="animate-spin" />}Save
            </Button>
            <FormSuccessful successful={form.recentlySuccessful} />
          </div>
        </Form>
      </CardContent>
    </Card>
  );
}
