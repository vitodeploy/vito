import { ServerLog } from '@/types/server-log';

export interface SSL {
  id: number;
  server_id: number;
  site_id: number | null;
  domain_id?: number;
  is_wildcard: boolean;
  has_csr: boolean;
  type: 'letsencrypt' | 'custom' | 'csr';
  log?: ServerLog;
  status: string;
  status_color: 'gray' | 'success' | 'info' | 'warning' | 'danger';
  domains?: string[];
  csr_data?: Record<string, string | number | null>;
  expires_at: string | null;
  created_at: string;
  updated_at: string;

  [key: string]: unknown;
}
