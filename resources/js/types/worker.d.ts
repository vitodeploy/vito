export interface Worker {
  id: number;
  server_id: number;
  name: string;
  command: string;
  user: string;
  auto_start: boolean;
  auto_restart: boolean;
  numprocs: number;
  status: string;
  status_color: 'gray' | 'success' | 'info' | 'warning' | 'danger';
  created_at: string;
  updated_at: string;
}
