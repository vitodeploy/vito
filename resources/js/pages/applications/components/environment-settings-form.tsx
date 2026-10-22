import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import InputError from '@/components/ui/input-error';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { useConfigs } from '@/stores/bootstrap-store';
import { Application, Environment, EnvironmentFormData, PrimaryCandidate } from '@/types/application';
import EnvironmentFields, { environmentDefaults } from './environment-fields';
import PrimaryComponentPicker from './primary-component-picker';
import { Row, SaveActions, Section, useSyncedDefaults } from './section';

export default function EnvironmentSettingsForm({ application, environment, primaryCandidates, disabled }: {
  application: Application;
  environment: Environment;
  primaryCandidates: PrimaryCandidate[];
  disabled: boolean;
}) {
  const preset = useConfigs()!.application.runtimes.find((runtime) => runtime.id === application.runtime);
  const values: EnvironmentFormData = {
    ...environmentDefaults(preset),
    name: environment.name,
    branch: environment.branch,
    runtime_version: environment.runtime_version ?? environmentDefaults(preset).runtime_version,
    primary_component_id: environment.primary_component_id ? String(environment.primary_component_id) : '',
    auto_deploy: environment.auto_deploy,
  };
  const form = useForm<EnvironmentFormData>(values);
  useSyncedDefaults(form, values);
  const known = ['name', 'branch', 'runtime_version', 'primary_component_id', ...(preset?.form ?? []).map((field) => field.name)];
  const unknownErrors = Object.entries(form.errors).filter(([key]) => !known.includes(key));
  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.put(`/applications/${application.id}/environments/${environment.id}`, { preserveScroll: true, onSuccess: () => form.setDefaults() });
  };

  return (
    <form onSubmit={submit}>
      <fieldset disabled={disabled || !preset || form.processing} className="contents">
        <Section title="Environment" description="Branch, runtime and release settings. Changes apply on the next deploy." footer={!disabled && preset && <SaveActions form={form} />}>
          {!preset && <p className="text-destructive px-4 py-3 text-sm">The {application.runtime} runtime plugin is missing. Restore it to edit this environment.</p>}
          <EnvironmentFields inline preset={preset} data={form.data} errors={form.errors} onChange={(name, value) => form.setData(name, value)} />
          <PrimaryComponentPicker inline value={String(form.data.primary_component_id)} onChange={(value) => form.setData('primary_component_id', value)} candidates={primaryCandidates} error={form.errors.primary_component_id} />
          <Row label={<Label htmlFor="auto-deploy">Deploy on push</Label>} description={`Pushing to ${form.data.branch || 'the branch'} starts a deployment.`}>
            <Switch id="auto-deploy" checked={Boolean(form.data.auto_deploy)} onCheckedChange={(value) => form.setData('auto_deploy', value)} />
          </Row>
          {unknownErrors.length > 0 && <div className="px-4 py-3">{unknownErrors.map(([key, message]) => <InputError key={key} message={message} />)}</div>}
        </Section>
      </fieldset>
    </form>
  );
}
