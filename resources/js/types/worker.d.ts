export interface Worker {
  id: number;
  server_id: number;
  site_id: number | null;
  name: string;
  command: string;
  user: string;
  auto_start: boolean;
  auto_restart: boolean;
  numprocs: number;
  status: string;
  status_color: 'gray' | 'success' | 'info' | 'warning' | 'danger';
  error: string | null;
  is_site_bootstrap: boolean;
  created_at: string;
  updated_at: string;
}
