export interface CronJob {
  id: number;
  name: string | null;
  server_id: number;
  site_id: number | null;
  component_id: number | null;
  component_url?: string | null;
  managed: boolean;
  command: string;
  user: string;
  frequency: string;
  status: string;
  status_color: 'gray' | 'success' | 'info' | 'warning' | 'danger';
  created_at: string;
  updated_at: string;
}
