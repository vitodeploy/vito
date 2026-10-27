import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import DynamicField from '@/components/ui/dynamic-field';
import InputError from '@/components/ui/input-error';
import { CapabilityConfiguration, ComponentOptions } from '@/types/application';

export default function CapabilityFields({
  options,
  value,
  onChange,
  errors,
}: {
  options: ComponentOptions['capabilities'];
  value: CapabilityConfiguration[];
  onChange: (value: CapabilityConfiguration[]) => void;
  errors: Record<string, string | undefined>;
}) {
  return (
    <>
      {options.map((option) => {
        const index = value.findIndex((capability) => capability.capability === option.id);
        const selected = value[index];
        return (
          <div className="flex flex-col gap-4 px-4 py-3" key={option.id}>
            <div className="flex min-h-9 items-center justify-between gap-4 text-sm">
              <div className="flex flex-col gap-0.5">
                <Label htmlFor={`capability-${option.id}`}>{option.label}</Label>
                <p className="text-muted-foreground">{option.description}</p>
              </div>
              <Switch
                id={`capability-${option.id}`}
                checked={!!selected}
                onCheckedChange={(checked) =>
                  onChange(
                    checked
                      ? [
                          ...value,
                          { capability: option.id, config: Object.fromEntries(option.form.map((field) => [field.name, field.default ?? ''])) },
                        ]
                      : value.filter((capability) => capability.capability !== option.id),
                  )
                }
              />
            </div>
            {selected && option.form.length > 0 && (
              <div className="grid gap-4 pb-1">
                {option.form.map((field) => (
                  <DynamicField
                    controlled
                    key={field.name}
                    config={field}
                    idPrefix={option.id}
                    value={selected.config[field.name]}
                    onChange={(fieldValue) =>
                      onChange(
                        value.map((capability) =>
                          capability.capability === option.id
                            ? { ...capability, config: { ...capability.config, [field.name]: fieldValue } }
                            : capability,
                        ),
                      )
                    }
                    error={errors[`capabilities.${index}.config.${field.name}`]}
                  />
                ))}
              </div>
            )}
            <InputError message={errors[`capabilities.${index}.capability`] ?? errors[`capabilities.${index}.config`]} />
          </div>
        );
      })}
      {errors.capabilities && (
        <div className="px-4 py-3">
          <InputError message={errors.capabilities} />
        </div>
      )}
    </>
  );
}
