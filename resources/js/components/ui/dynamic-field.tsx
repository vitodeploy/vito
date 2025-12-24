import { InputHTMLAttributes, useEffect, useState } from 'react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { PasswordInput } from '@/components/ui/password-input';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectGroup, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { DynamicFieldConfig } from '@/types/dynamic-field-config';
import InputError from '@/components/ui/input-error';
import { FormField } from '@/components/ui/form';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { TriangleAlertIcon } from 'lucide-react';
import ServerProviderSelect from '@/pages/server-providers/components/server-provider-select';

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
          {!Array.isArray(config.options) && config.options?.type === 'warning' && <TriangleAlertIcon className="text-warning!" />}
          {config.label && <AlertTitle>{config.label}</AlertTitle>}
          <AlertDescription>
            {config.description}
            {config.link && (
              <a href={config.link.url} target="_blank" className="text-primary underline">
                {config.link.label}
              </a>
            )}
          </AlertDescription>
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
              {Array.isArray(config.options) &&
                config.options.map((item) => (
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

  // Handle textarea
  if (config?.type === 'textarea') {
    return (
      <FormField>
        <Label htmlFor={`field-${config.name}`} className="capitalize">
          {label}
        </Label>
        <Textarea
          name={config.name}
          id={`field-${config.name}`}
          defaultValue={(value as string) || ''}
          placeholder={config.placeholder}
          onChange={(e) => onChange(e.target.value)}
          className={config.className}
        />
        {config.description && <p className="text-muted-foreground text-xs">{config.description}</p>}
        <InputError message={error} />
      </FormField>
    );
  }

  // Handle password
  if (config?.type === 'password') {
    return (
      <FormField>
        <Label htmlFor={`field-${config.name}`} className="capitalize">
          {label}
        </Label>
        <Input
          type="password"
          name={config.name}
          id={`field-${config.name}`}
          defaultValue={(value as string) || ''}
          placeholder={config.placeholder}
          onChange={(e) => onChange(e.target.value)}
          autoComplete="off"
          spellCheck={false}
        />
        {config.description && <p className="text-muted-foreground text-xs">{config.description}</p>}
        <InputError message={error} />
      </FormField>
    );
  }

  // Handle password with visibility toggle
  if (config?.type === 'password-with-toggle') {
    return (
      <FormField>
        <Label htmlFor={`field-${config.name}`} className="capitalize">
          {label}
        </Label>
        <PasswordInput
          name={config.name}
          id={`field-${config.name}`}
          defaultValue={(value as string) || ''}
          placeholder={config.placeholder}
          onChange={(e) => onChange(e.target.value)}
          autoComplete="off"
          spellCheck={false}
        />
        {config.description && <p className="text-muted-foreground text-xs">{config.description}</p>}
        <InputError message={error} />
      </FormField>
    );
  }

  // Handle server provider select
  if (config?.type === 'component' && config?.name === 'server_provider') {
    return (
      <FormField>
        <Label htmlFor={`field-${config.name}`} className="capitalize">
          {label}
        </Label>
        <ServerProviderSelect value={value as string} onValueChange={(value) => onChange(value)} />
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
        id={`field-${config.name}`}
        defaultValue={(value as string) || ''}
        onChange={(e) => onChange(e.target.value)}
        {...props}
      />
      {config.description && <p className="text-muted-foreground text-xs">{config.description}</p>}
      <InputError message={error} />
    </FormField>
  );
}
