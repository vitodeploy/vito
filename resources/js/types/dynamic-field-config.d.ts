export interface DynamicFieldConfig {
  type: 'text' | 'select' | 'checkbox' | 'component' | 'alert';
  name: string;
  options?: string[] | { [key: string]: string };
  component?: string;
  placeholder?: string;
  description?: string;
  label?: string;
  default?: string | number | boolean;
  link?: {
    label: string;
    url: string;
  };
}
