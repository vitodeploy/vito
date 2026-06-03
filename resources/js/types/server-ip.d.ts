export interface ServerIpAddress {
  id: number;
  server_id: number;
  ip: string;
  prefix_length: number;
  family: 'IPv4' | 'IPv6';
  interface: string | null;
  type: string;
  type_color: 'gray' | 'info' | 'warning';
  status: string;
  status_color: 'success' | 'info' | 'warning' | 'danger';
  is_managed: boolean;
  is_primary: boolean;
  created_at: string;
  updated_at: string;
}
