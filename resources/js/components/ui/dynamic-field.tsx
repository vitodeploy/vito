import { InputHTMLAttributes, useEffect, useState } from 'react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Switch } from '@/components/ui/switch';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DynamicFieldConfig } from '@/types/dynamic-field-config';
import InputError from '@/components/ui/input-error';
import { FormField } from '@/components/ui/form';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

interface DynamicFieldProps {
  value: string | number | boolean | string[] | undefined;
  onChange: (value: string | number | boolean | string[]) => void;
  config: DynamicFieldConfig;
  error?: string;
}

export default function DynamicField({ value, onChange, config, error }: DynamicFieldProps) {
  const defaultLabel = config.name.replaceAll('_', ' ');
  const label = config?.label || defaultLabel;
  const [initialValue, setInitialValue] = useState(false);

  if (!value) {
    value = config?.default || '';
  }

  useEffect(() => {
    if (!initialValue) {
      if (config.type === 'checkbox') {
        onChange((value as boolean) || false);
      } else {
        onChange(value);
      }
      setInitialValue(true);
    }
  }, [initialValue, setInitialValue, onChange, value, config]);

  // Handle alert
  if (config?.type === 'alert') {
    return (
      <FormField>
        <Alert>
          <AlertTitle>{config.label}</AlertTitle>
          <AlertDescription>{config.description}</AlertDescription>
        </Alert>
      </FormField>
    );
  }

  // Handle checkbox
  if (config?.type === 'checkbox') {
    return (
      <FormField>
        <div className="flex items-center space-x-2">
          <Switch id={`switch-${config.name}`} defaultChecked={value as boolean} onCheckedChange={onChange} />
          <Label htmlFor={`switch-${config.name}`}>{label}</Label>
          {config.description && <p className="text-muted-foreground text-xs">{config.description}</p>}
          <InputError message={error} />
        </div>
      </FormField>
    );
  }

  // Handle select
  if (config?.type === 'select' && config.options) {
    return (
      <FormField>
        <Label htmlFor={`field-${config.name}`} className="capitalize">
          {label}
        </Label>
        <Select defaultValue={value as string} onValueChange={onChange}>
          <SelectTrigger id={`field-${config.name}`}>
            <SelectValue placeholder={config.placeholder || `Select ${label}`} />
          </SelectTrigger>
          <SelectContent>
            <SelectGroup>
              {config.options.map((item) => (
                <SelectItem key={`${config.name}-${item}`} value={item}>
                  {item}
                </SelectItem>
              ))}
            </SelectGroup>
          </SelectContent>
        </Select>
        {config.description && <p className="text-muted-foreground text-xs">{config.description}</p>}
        <InputError message={error} />
      </FormField>
    );
  }

  // Default to text input
  const props: InputHTMLAttributes<HTMLInputElement> = {};
  if (config?.placeholder) {
    props.placeholder = config.placeholder;
  }

  return (
    <FormField>
      <Label htmlFor={`field-${config.name}`} className="capitalize">
        {label}
      </Label>
      <Input
        type="text"
        name={config.name}
        id={config.name}
        defaultValue={(value as string) || ''}
        onChange={(e) => onChange(e.target.value)}
        {...props}
      />
      {config.description && <p className="text-muted-foreground text-xs">{config.description}</p>}
      <InputError message={error} />
    </FormField>
  );
}
