export interface Redirect {
  id: number;
  server_id: number;
  site_id: number;
  from: string;
  to: string;
  mode: string;
  websocket: boolean;
  status: string;
  status_color: 'gray' | 'success' | 'info' | 'warning' | 'danger';
  created_at: string;
  updated_at: string;

  [key: string]: unknown;
}
