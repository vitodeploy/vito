import { Server } from '@/types/server';
import { DynamicFieldConfig } from '@/types/dynamic-field-config';

export interface Site {
  id: number;
  server_id: number;
  server?: Server;
  source_control_id: number;
  type: string;
  type_data: {
    method?: 'round-robin' | 'least-connections' | 'ip-hash';
    env_path?: string;
    [key: string]: unknown;
  };
  domain: string;
  web_directory: string;
  webserver: string;
  webserver_creates_site_ssls: boolean;
  can_configure_ssl: boolean;
  webserver_allowed_ssl_methods: string[] | null;
  webserver_default_ssl_method: string;
  path: string;
  php_version: string;
  php_settings: {
    max_upload_size: number | null;
    max_execution_time: number | null;
    memory_limit: number | null;
    max_input_vars: number | null;
  };
  supports_php_settings: boolean;
  repository: string;
  branch?: string;
  status: string;
  status_color: 'gray' | 'success' | 'info' | 'warning' | 'danger';
  auto_deploy: boolean;
  port: number;
  user: string;
  isolated_user_id: number | null;
  url: string;
  force_ssl: boolean;
  ssl_enabled: boolean;
  progress: number;
  progress_step: string | null;
  last_error: string | null;
  vhost_generation_enabled: boolean;
  has_custom_vhost_template: boolean;
  features: SiteFeature[];
  modern_deployment: boolean;
  stats_enabled: boolean;
  is_proxied_site_type: boolean;
  available_tooling_commands: string[];
  start_command: string | null;
  bootstrap_worker_id: number | null;
  basic_auth: {
    enabled: boolean;
    users: { username: string }[];
  };
  warnings: SiteWarning[];
  created_at: string;
  updated_at: string;

  [key: string]: unknown;
}

export interface SiteType {
  label: string;
  handler: string;
  form?: DynamicFieldConfig[];
  features?: SiteFeature[];
}

export interface SiteFeature {
  label: string;
  description?: string;
  actions?: {
    [key: string]: SiteFeatureAction;
  };
}

export type SiteWarning =
  | { key: 'pending_domains'; count: number; domains: string[] }
  | { key: 'ssl_disabled' }
  | { key: 'vhost_generation_disabled' }
  | { key: 'php_settings_ignored' }
  | { key: 'ssl_expiring'; count: number; domains: string[]; earliest_expiry: string }
  | { key: 'needs_first_deploy' }
  | {
      key: 'worker_not_running';
      worker_id: number;
      name: string | null;
      status: string;
      status_color: 'gray' | 'success' | 'info' | 'warning' | 'danger';
      error: string | null;
    };

export interface SiteFeatureAction {
  label: string;
  handler: string;
  form?: DynamicFieldConfig[];
  active?: boolean;
}
