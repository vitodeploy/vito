export interface ServerIpAddress {
  id: number;
  server_id: number;
  ip: string;
  prefix_length: number;
  family: string;
  interface: string | null;
  type: string;
  type_color: 'gray' | 'info' | 'warning';
  status: string;
  status_color: 'gray' | 'success' | 'info' | 'warning' | 'danger';
  is_managed: boolean;
  is_primary: boolean;
  created_at: string;
  updated_at: string;
}
