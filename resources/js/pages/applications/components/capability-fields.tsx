import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import DynamicField from '@/components/ui/dynamic-field';
import InputError from '@/components/ui/input-error';
import { CapabilityConfiguration, ComponentOptions } from '@/types/application';

export default function CapabilityFields({ options, value, onChange, errors }: {
  options: ComponentOptions['capabilities'];
  value: CapabilityConfiguration[];
  onChange: (value: CapabilityConfiguration[]) => void;
  errors: Record<string, string | undefined>;
}) {
  return (
    <div className="flex flex-col gap-4">
      <p className="font-medium">Capabilities</p>
      {options.map((option) => {
        const index = value.findIndex((capability) => capability.capability === option.id);
        const selected = value[index];
        return (
          <div className="flex flex-col gap-3 rounded-md border p-3" key={option.id}>
            <div className="flex items-center gap-2">
              <Checkbox id={`capability-${option.id}`} checked={!!selected} onCheckedChange={(checked) => onChange(checked === true
                ? [...value, { capability: option.id, config: Object.fromEntries(option.form.map((field) => [field.name, field.default ?? ''])) }]
                : value.filter((capability) => capability.capability !== option.id))} />
              <Label htmlFor={`capability-${option.id}`}>{option.label}</Label>
            </div>
            <p className="text-muted-foreground text-sm">{option.description}</p>
            {selected && option.form.map((field) => <DynamicField key={field.name} config={field} idPrefix={option.id} value={selected.config[field.name]} onChange={(fieldValue) => onChange(value.map((capability) => capability.capability === option.id
              ? { ...capability, config: { ...capability.config, [field.name]: fieldValue } } : capability))} error={errors[`capabilities.${index}.config.${field.name}`]} />)}
            <InputError message={errors[`capabilities.${index}.capability`]} />
            <InputError message={errors[`capabilities.${index}.config`]} />
          </div>
        );
      })}
      <InputError message={errors.capabilities} />
    </div>
  );
}
