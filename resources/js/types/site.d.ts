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
  aliases?: string[];
  web_directory: string;
  webserver: string;
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
  progress: number;
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

export interface SiteFeatureAction {
  label: string;
  handler: string;
  form?: DynamicFieldConfig[];
  active?: boolean;
}
