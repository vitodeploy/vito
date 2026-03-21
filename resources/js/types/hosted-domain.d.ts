export interface HostedDomain {
  id: number;
  site_id: number;
  server_id: number;
  domain: string;
  type: 'primary' | 'alias' | 'redirect';
  type_color: 'gray' | 'success' | 'info' | 'warning' | 'danger';
  status: string;
  status_color: 'gray' | 'success' | 'info' | 'warning' | 'danger';
  ssl_method: 'none' | 'letsencrypt' | 'custom';
  ssl_id: number | null;
  error: string | null;
  ssl_type: string | null;
  ssl_domains: string[] | null;
  ssl_expires_at: string | null;
  created_at: string;
  updated_at: string;

  [key: string]: unknown;
}

export interface AvailableSsl {
  id: number;
  label: string;
  domains: string[];
}
