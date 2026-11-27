export type DynamicFieldValue = string | number | boolean | string[];

export interface SetupGuideStep {
  title: string;
  description?: string;
  code?: string;
}

export interface DynamicFieldConfig {
  type:
    | 'text'
    | 'password'
    | 'password-with-toggle'
    | 'textarea'
    | 'select'
    | 'checkbox'
    | 'component'
    | 'alert'
    | 'guide'
    | 'tooling'
    | 'tooling-picker'
    | 'tooling-selector'
    | 'tags'
    | 'resource';
  name: string;
  options?: string[] | { [key: string]: string };
  optionLabels?: { [key: string]: string };
  component?: string;
  placeholder?: string;
  description?: string;
  label?: string;
  default?: string | number | boolean | string[];
  link?: {
    label: string;
    url: string;
  };
  className?: string;
  componentProps?: Record<string, unknown>;
  width?: 'half' | 'third' | null;
}
