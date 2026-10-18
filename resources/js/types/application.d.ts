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
  needs_redeploy: boolean;
  live_run_id: number | null;
  shared_paths: string[];
  keep_releases: number;
  can_deploy: boolean;
  last_error: string | null;
  status: string;
  status_color: 'success' | 'danger';
  created_at: string;
  updated_at: string;
}

export type DeploymentStage = 'clone' | 'build' | 'Release command' | 'release' | 'post-release';

export interface ComponentDeployment {
  id: number;
  component_id: number | null;
  component_name: string | null;
  server_id: number | null;
  server_name: string | null;
  log_id: number | null;
  current_stage: DeploymentStage | null;
  status: 'deploying' | 'finished' | 'failed';
  status_color: 'warning' | 'success' | 'danger';
  active: boolean;
}

export interface EnvironmentDeployment {
  id: number;
  environment_id: number;
  status: 'pending' | 'running' | 'finished' | 'failed' | 'partial' | 'cancelled' | 'rolled_back' | 'superseded';
  status_color: 'warning' | 'success' | 'danger' | 'gray';
  current_stage: DeploymentStage | null;
  commit_id: string | null;
  commit_data: { message?: string | null } | null;
  release: string;
  branch: string;
  primary_component_id: number | null;
  triggered_by: string | null;
  is_join: boolean;
  is_recovering: boolean;
  started_at: string | null;
  finished_at: string | null;
  created_at: string;
  deployments: ComponentDeployment[];
}

export interface EnvironmentVariable {
  id: number | null;
  key: string;
  value: string | null;
  is_secret: boolean;
  has_value: boolean;
  source: string;
  component_name: string | null;
}

export type EnvironmentVariablePreview = Pick<EnvironmentVariable, 'key' | 'is_secret'>;

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
