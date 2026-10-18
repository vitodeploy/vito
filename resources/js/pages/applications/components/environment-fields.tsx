import { FormField } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import DynamicField from '@/components/ui/dynamic-field';
import { EnvironmentFormData, RuntimePreset } from '@/types/application';
import { DynamicFieldConfig, DynamicFieldValue } from '@/types/dynamic-field-config';

export function environmentDefaults(preset?: RuntimePreset): EnvironmentFormData {
  return {
    ...Object.fromEntries((preset?.form ?? []).map((field) => [field.name, field.default ?? ''])),
    name: 'Production',
    branch: 'main',
    runtime_version: String(preset?.form.find((field) => field.name === 'runtime_version')?.default ?? ''),
  };
}

export default function EnvironmentFields({
  preset,
  data,
  errors,
  onChange,
  runtimeLocked = false,
}: {
  preset?: RuntimePreset;
  runtimeLocked?: boolean;
  data: EnvironmentFormData;
  errors: Record<string, string | undefined>;
  onChange: (name: string, value: DynamicFieldValue) => void;
}) {
  const fields: DynamicFieldConfig[] = (preset?.form ?? []).filter((field) => field.name !== 'name' && field.name !== 'branch');
  if (preset && preset.versions.length > 0 && !fields.some((field) => field.name === 'runtime_version')) {
    fields.unshift({ name: 'runtime_version', label: 'Runtime version', type: 'select', options: preset.versions });
  }

  return (
    <>
      <FormField>
        <Label htmlFor="environment-name">Environment name</Label>
        <Input id="environment-name" value={data.name} onChange={(e) => onChange('name', e.target.value)} />
        <InputError message={errors.name} />
      </FormField>
      <FormField>
        <Label htmlFor="environment-branch">Branch</Label>
        <Input id="environment-branch" value={data.branch} onChange={(e) => onChange('branch', e.target.value)} />
        <InputError message={errors.branch} />
      </FormField>
      {fields.map((field) => runtimeLocked && field.name === 'runtime_version' ? (
        <FormField key={field.name}>
          <Label htmlFor="environment-runtime">Runtime version</Label>
          <Input id="environment-runtime" value={data.runtime_version} disabled />
          <p className="text-muted-foreground text-sm">Remove all components before changing the runtime version.</p>
          <InputError message={errors.runtime_version} />
        </FormField>
      ) : (
        <DynamicField
          key={`${preset?.id}-${field.name}`}
          config={field}
          value={data[field.name]}
          onChange={(value) => onChange(field.name, value)}
          error={errors[field.name]}
        />
      ))}
      {!fields.some((field) => field.name === 'runtime_version') && <InputError message={errors.runtime_version} />}
    </>
  );
}
