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
  repository: string;
  branch?: string;
  status: string;
  status_color: 'gray' | 'success' | 'info' | 'warning' | 'danger';
  auto_deploy: boolean;
  port: number;
  user: string;
  url: string;
  force_ssl: boolean;
  ssl_enabled: boolean;
  progress: number;
  vhost_generation_enabled: boolean;
  features: SiteFeature[];
  modern_deployment: boolean;
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
  | { key: 'ssl_expiring'; count: number; domains: string[]; earliest_expiry: string }
  | { key: string; [k: string]: unknown };

export interface SiteFeatureAction {
  label: string;
  handler: string;
  form?: DynamicFieldConfig[];
  active?: boolean;
}
