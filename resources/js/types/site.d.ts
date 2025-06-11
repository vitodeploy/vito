import { Server } from '@/types/server';

export interface Site {
  id: number;
  server_id: number;
  server?: Server;
  source_control_id: number;
  type: string;
  type_data: {
    method?: 'round-robin' | 'least-connections' | 'ip-hash';
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
