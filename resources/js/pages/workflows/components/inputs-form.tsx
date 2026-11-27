import { Dispatch, SetStateAction, useState } from 'react';
import { Editor } from '@monaco-editor/react';
import { BracesIcon, ListIcon } from 'lucide-react';
import { toast } from 'sonner';
import { useClipboard } from '@/hooks/use-clipboard';
import DynamicField, { dynamicFieldSpan } from '@/components/ui/dynamic-field';
import { FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { TagsInput } from '@/components/ui/tags-input';
import { useAppearance } from '@/hooks/use-appearance';
import { DynamicFieldConfig, DynamicFieldValue } from '@/types/dynamic-field-config';
import { WorkflowInputs, WorkflowVariable } from '@/types/workflow-action';
import ResourceSelect from './resource-select';

function scalar(value: unknown): string {
  return typeof value === 'string' || typeof value === 'number' ? String(value) : '';
}

export function cleanInputs(inputs: WorkflowInputs): WorkflowInputs {
  return Object.fromEntries(Object.entries(inputs).filter(([, value]) => value !== '' && value !== null && value !== undefined));
}

export default function InputsForm({
  fields,
  inputs,
  onChange,
  onInvalidChange,
  variables = [],
}: {
  fields: DynamicFieldConfig[];
  inputs: WorkflowInputs;
  onChange: Dispatch<SetStateAction<WorkflowInputs>>;
  onInvalidChange: (invalid: boolean) => void;
  variables?: WorkflowVariable[];
}) {
  const { getActualAppearance } = useAppearance();
  const [mode, setMode] = useState<'form' | 'json'>(fields.length > 0 ? 'form' : 'json');
  const [json, setJson] = useState(() => JSON.stringify(inputs, null, 2));
  const { copy } = useClipboard();

  const setField = (name: string, value: DynamicFieldValue) =>
    onChange((current) => {
      const dependents = fields.filter((field) => field.componentProps?.parent === name && current[field.name] !== undefined);
      if (current[name] === value || dependents.length === 0) {
        return { ...current, [name]: value };
      }
      return { ...current, ...Object.fromEntries(dependents.map((field) => [field.name, ''])), [name]: value };
    });

  const toggleMode = () => {
    if (mode === 'form') {
      setJson(JSON.stringify(inputs, null, 2));
      setMode('json');
      return;
    }
    try {
      onChange(parseInputs(json));
      setMode('form');
    } catch {
      toast.error('Fix the JSON before switching back to the form.');
    }
  };

  const updateJson = (value: string) => {
    setJson(value);
    try {
      onChange(parseInputs(value));
      onInvalidChange(false);
    } catch {
      onInvalidChange(true);
    }
  };

  const renderField = (field: DynamicFieldConfig) => {
    const fieldId = `workflow-input-${field.name}`;
    const label = field.label || field.name.replaceAll('_', ' ');
    const value = inputs[field.name] ?? field.default;

    if (field.type === 'alert' || field.type === 'guide') {
      return <DynamicField value="" onChange={() => {}} config={field} />;
    }

    if (field.type !== 'resource' && field.type !== 'tags') {
      return <DynamicField controlled value={value} onChange={(v) => setField(field.name, v)} config={field} idPrefix="workflow" />;
    }

    return (
      <FormField>
        <Label htmlFor={fieldId} className="capitalize">
          {label}
        </Label>
        {field.type === 'resource' ? (
          <ResourceSelect
            id={fieldId}
            resource={scalar(field.componentProps?.resource)}
            parent={field.componentProps?.parent ? scalar(inputs[scalar(field.componentProps.parent)]) : undefined}
            value={scalar(value)}
            label={label}
            variables={variables}
            onChange={(v) => setField(field.name, v)}
          />
        ) : (
          <TagsInput value={Array.isArray(value) ? value.map(String) : []} onValueChange={(tags) => setField(field.name, tags)} placeholder={field.placeholder} />
        )}
        {field.description && <p className="text-muted-foreground text-xs">{field.description}</p>}
      </FormField>
    );
  };

  return (
    <div className="flex flex-col gap-4">
      <div className="flex items-center justify-between">
        <span className="text-sm font-medium">Inputs</span>
        <Button type="button" variant="ghost" size="sm" onClick={toggleMode} disabled={fields.length === 0}>
          {mode === 'form' ? <BracesIcon /> : <ListIcon />}
          {mode === 'form' ? 'Edit as JSON' : 'Edit as form'}
        </Button>
      </div>
      {mode === 'form' ? (
        <FormFields className="sm:grid-cols-6">
          {fields.map((field) => (
            <div key={`field-${field.name}`} className={dynamicFieldSpan(field)}>
              {renderField(field)}
            </div>
          ))}
        </FormFields>
      ) : (
        <Editor
          defaultLanguage="json"
          value={json}
          theme={getActualAppearance() === 'dark' ? 'vs-dark' : 'vs'}
          className="h-[400px]"
          onChange={(value) => updateJson(value ?? '')}
          options={{ fontSize: 15, minimap: { enabled: false }, wordWrap: 'on', ariaLabel: 'Inputs (JSON)' }}
        />
      )}
      {variables.length > 0 && (
        <div className="flex flex-col gap-2">
          <span className="text-muted-foreground text-xs">Outputs of the previous action. Click to copy, then paste into any field.</span>
          <div className="flex flex-wrap gap-1">
            {variables.map((variable) => (
              <button key={`var-${variable.key}`} type="button" onClick={() => copy(`{${variable.key}}`)} title={variable.description}>
                <Badge variant="outline" className="hover:bg-accent font-mono">{`{${variable.key}}`}</Badge>
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}

function parseInputs(json: string): WorkflowInputs {
  const parsed = JSON.parse(json || '{}');
  if (Array.isArray(parsed) && parsed.length === 0) {
    return {};
  }
  if (typeof parsed !== 'object' || parsed === null || Array.isArray(parsed)) {
    throw new Error('Inputs must be a JSON object');
  }
  return parsed;
}
