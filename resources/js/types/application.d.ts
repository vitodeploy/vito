import { DynamicFieldConfig } from '@/types/dynamic-field-config';

export interface Application {
  id: number;
  server_id: number;
  type: string;
  type_data: Record<string, unknown>;
  domain: string;
  aliases?: string[];
  force_ssl: boolean;
  has_custom_template: boolean;
  status: string;
  status_color: 'gray' | 'success' | 'info' | 'warning' | 'danger';
  url: string;
  created_at: string;
  updated_at: string;

  [key: string]: unknown;
}

export interface ApplicationType {
  label: string;
  handler: string;
  form?: DynamicFieldConfig[];
  edit_form?: DynamicFieldConfig[];
  settings_fields?: SettingsFieldConfig[];
  deploy_action?: string;
}

export interface SettingsFieldConfig {
  name: string;
  field_type: 'info' | 'sidebar-action';
  label: string;
  // info fields
  source?: 'application' | 'type_data';
  key?: string;
  format?: 'text' | 'badge' | 'boolean' | 'link';
  // sidebar-action fields
  action_label?: string;
  action_type?: 'sidebar';
  load_route?: string;
  submit_route?: string;
  submit_method?: string;
  reset_route?: string;
  form?: DynamicFieldConfig[];
}
