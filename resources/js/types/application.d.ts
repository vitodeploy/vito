import { Configs } from '@/types';
import { DynamicFieldConfig, DynamicFieldValue } from '@/types/dynamic-field-config';

export interface Application {
  id: number;
  project_id: number;
  source_control_id: number | null;
  name: string;
  repository: string;
  runtime: string;
  status: string;
  status_color: 'success' | 'danger';
  created_at: string;
  updated_at: string;
}

export interface Environment {
  id: number;
  application_id: number;
  name: string;
  slug: string;
  branch: string;
  runtime_version: string | null;
  primary_component_id: number | null;
  components_count?: number;
  last_error: string | null;
  status: string;
  status_color: 'success' | 'danger';
  created_at: string;
  updated_at: string;
}

export interface Component {
  id: number;
  environment_id: number;
  environment_status?: string;
  application_status?: string;
  server_id: number;
  server_name: string;
  name: string;
  type: string;
  type_color: 'info' | 'warning';
  user: string;
  path: string;
  status: string;
  status_color: 'success' | 'danger' | 'warning' | 'gray';
  last_error: string | null;
  needs_redeploy: boolean;
  ssh_key: string | null;
  capabilities: string[];
  capability_configs: (CapabilityConfiguration & { status: string; status_color: Component['status_color'] })[];
  surfaces: string[];
  created_at: string;
  updated_at: string;
}

export interface ComponentOptions {
  servers: { id: number; name: string; ssh_user: string }[];
  capabilities: { id: string; label: string; description: string; form: DynamicFieldConfig[] }[];
}

export type CapabilityConfiguration = { capability: string; config: Record<string, DynamicFieldValue> };
export type ComponentProcessOwner = { user: string; url: string };
export type PrimaryCandidate = { id: number; name: string };
export type RuntimePreset = Configs['application']['runtimes'][number];

export type EnvironmentFormData = {
  name: string;
  branch: string;
  runtime_version: string;
  [key: string]: DynamicFieldValue;
};
