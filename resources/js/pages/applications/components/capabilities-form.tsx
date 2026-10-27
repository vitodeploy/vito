import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { Application, Component, ComponentOptions, Environment } from '@/types/application';
import CapabilityFields from './capability-fields';
import { SaveActions, Section, useSyncedDefaults } from './section';

export default function CapabilitiesForm({
  application,
  environment,
  component,
  capabilities,
  disabled,
}: {
  application: Application;
  environment: Environment;
  component: Component;
  capabilities: ComponentOptions['capabilities'];
  disabled: boolean;
}) {
  const values = { capabilities: component.capability_configs.map(({ capability, config }) => ({ capability, config })) };
  const form = useForm(values);
  useSyncedDefaults(form, values);
  const removed = component.capability_configs
    .filter(({ capability }) => !form.data.capabilities.some((item) => item.capability === capability))
    .map(({ capability }) => capabilities.find((option) => option.id === capability)?.label ?? capability);
  const submit = (event: FormEvent) => {
    event.preventDefault();
    form.put(`/applications/${application.id}/environments/${environment.id}/components/${component.id}/capabilities`, {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => form.setDefaults(),
    });
  };

  return (
    <form onSubmit={submit}>
      <fieldset disabled={disabled || form.processing} className="contents">
        <Section
          title="Capabilities"
          description="Turn on what this component should run. Changes apply on the next deploy."
          footer={!disabled && <SaveActions form={form} />}
        >
          <CapabilityFields
            options={capabilities}
            value={form.data.capabilities}
            onChange={(value) => form.setData('capabilities', value)}
            errors={form.errors}
          />
          {removed.length > 0 && (
            <p className="text-warning px-4 py-3 text-sm">
              Turning off {removed.join(', ')} removes its server resources, including any of its workers.
            </p>
          )}
        </Section>
      </fieldset>
    </form>
  );
}
