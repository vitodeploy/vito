export interface FirewallRule {
  id: number;
  name: string;
  server_id: number;
  type: string;
  protocol: string;
  port: number;
  source: string;
  mask: number;
  note: string;
  status: string;
  status_color: 'gray' | 'success' | 'info' | 'warning' | 'danger';
  created_at: string;
  updated_at: string;
}
