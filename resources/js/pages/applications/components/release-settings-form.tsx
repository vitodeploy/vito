import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Application, Environment } from '@/types/application';
import { Field, SaveActions, Section, useSyncedDefaults } from './section';

export default function ReleaseSettingsForm({
  application,
  environment,
  disabled,
}: {
  application: Application;
  environment: Environment;
  disabled: boolean;
}) {
  const values = { shared_paths: environment.shared_paths.join('\n'), keep_releases: environment.keep_releases };
  const form = useForm(values);
  useSyncedDefaults(form, values);
  const pathErrors = Object.entries(form.errors)
    .filter(([key]) => key.startsWith('shared_paths'))
    .map(([, message]) => message);
  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.transform((data) => ({
      ...data,
      shared_paths: data.shared_paths
        .split('\n')
        .map((path) => path.trim())
        .filter(Boolean),
    }));
    form.put(`/applications/${application.id}/environments/${environment.id}/deployment-settings`, {
      preserveScroll: true,
      onSuccess: () => form.setDefaults(),
    });
  };

  return (
    <form onSubmit={submit}>
      <fieldset disabled={disabled || form.processing} className="contents">
        <Section
          title="Releases"
          description="What survives between releases, and how many to keep for rollbacks."
          footer={!disabled && <SaveActions form={form} />}
        >
          <Field inline id="shared-paths" label="Shared paths" description="One relative path per line." error={pathErrors.join(' ') || undefined}>
            <Textarea
              id="shared-paths"
              className="font-mono"
              placeholder={'storage\npublic/uploads'}
              value={form.data.shared_paths}
              onChange={(e) => form.setData('shared_paths', e.target.value)}
              rows={3}
            />
          </Field>
          <Field inline id="keep-releases" label="Releases to keep" description="The live release is always kept." error={form.errors.keep_releases}>
            <Input
              id="keep-releases"
              type="number"
              min={1}
              max={100}
              value={form.data.keep_releases}
              onChange={(e) => form.setData('keep_releases', Number(e.target.value))}
            />
          </Field>
        </Section>
      </fieldset>
    </form>
  );
}
