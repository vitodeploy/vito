export interface DynamicFieldConfig {
  type: 'text' | 'select' | 'checkbox' | 'component' | 'alert';
  name: string;
  options?: string[];
  placeholder?: string;
  description?: string;
  label?: string;
  default?: string | number | boolean;
}
