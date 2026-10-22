import { Input } from '@/components/ui/input';
import DynamicField from '@/components/ui/dynamic-field';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { EnvironmentFormData, RuntimePreset } from '@/types/application';
import { DynamicFieldValue } from '@/types/dynamic-field-config';
import { Field } from './section';

export function environmentDefaults(preset?: RuntimePreset): EnvironmentFormData {
  return {
    ...Object.fromEntries((preset?.form ?? []).map((field) => [field.name, field.default ?? ''])),
    name: 'Production',
    branch: 'main',
    runtime_version: String(preset?.form.find((field) => field.name === 'runtime_version')?.default ?? preset?.versions[0] ?? ''),
  };
}

export default function EnvironmentFields({
  preset,
  data,
  errors,
  onChange,
  inline,
}: {
  preset?: RuntimePreset;
  data: EnvironmentFormData;
  errors: Record<string, string | undefined>;
  onChange: (name: string, value: DynamicFieldValue) => void;
  inline?: boolean;
}) {
  const fields = (preset?.form ?? []).filter((field) => field.name !== 'name' && field.name !== 'branch');

  return (
    <>
      <Field id="environment-name" label="Name" inline={inline} error={errors.name}>
        <Input id="environment-name" placeholder="Staging" value={data.name} onChange={(e) => onChange('name', e.target.value)} />
      </Field>
      <Field id="environment-branch" label="Branch" inline={inline} error={errors.branch}>
        <Input id="environment-branch" className="font-mono" value={data.branch} onChange={(e) => onChange('branch', e.target.value)} />
      </Field>
      {preset && preset.versions.length > 0 && !fields.some((field) => field.name === 'runtime_version') && (
        <Field id="environment-runtime-version" label="Runtime version" inline={inline} error={errors.runtime_version}>
          <Select value={String(data.runtime_version ?? '')} onValueChange={(value) => onChange('runtime_version', value)}>
            <SelectTrigger id="environment-runtime-version"><SelectValue placeholder="Select a version" /></SelectTrigger>
            <SelectContent>
              {preset.versions.map((version) => <SelectItem key={version} value={version}>{version}</SelectItem>)}
            </SelectContent>
          </Select>
        </Field>
      )}
      {fields.map((field) => (
        <div key={`${preset?.id}-${field.name}`} className={inline ? 'px-4 py-3' : undefined}>
          <DynamicField config={field} value={data[field.name]} onChange={(value) => onChange(field.name, value)} error={errors[field.name]} />
        </div>
      ))}
    </>
  );
}
