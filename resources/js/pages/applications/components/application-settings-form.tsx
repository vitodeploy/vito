import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { Application } from '@/types/application';
import { SourceControl } from '@/types/source-control';
import ApplicationFields from './application-fields';
import { Row, SaveActions, Section, useSyncedDefaults } from './section';

export default function ApplicationSettingsForm({ application, runtime, sourceControls, disabled }: {
  application: Application;
  runtime: string;
  sourceControls: SourceControl[];
  disabled: boolean;
}) {
  const values = {
    name: application.name,
    repository: application.repository,
    source_control_id: application.source_control_id ? String(application.source_control_id) : '',
  };
  const form = useForm(values);
  useSyncedDefaults(form, values);
  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.put(`/applications/${application.id}`, { preserveScroll: true, onSuccess: () => form.setDefaults() });
  };

  return (
    <form onSubmit={submit}>
      <fieldset disabled={disabled || form.processing} className="contents">
        <Section
          title="Application"
          description="Shared by every environment. Changing the repository re-registers deploy keys on every component right away; the running release changes on the next deploy."
          footer={!disabled && <SaveActions form={form} />}
        >
          <ApplicationFields inline data={form.data} errors={form.errors} onChange={(name, value) => form.setData(name, value)} sourceControls={sourceControls} />
          <Row label="Runtime" description="Can't be changed after creation.">{runtime}</Row>
        </Section>
      </fieldset>
    </form>
  );
}
