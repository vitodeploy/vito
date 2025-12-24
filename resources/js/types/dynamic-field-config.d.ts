export interface DynamicFieldConfig {
  type: 'text' | 'password' | 'password-with-toggle' | 'textarea' | 'select' | 'checkbox' | 'component' | 'alert';
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
  className?: string;
  componentProps?: Record<string, unknown>;
}
