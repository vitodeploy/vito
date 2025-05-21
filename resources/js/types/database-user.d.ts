export interface DatabaseUser {
  id: number;
  server_id: number;
  username: string;
  databases: string[];
  host?: string;
  status: string;
  status_color: 'gray' | 'success' | 'info' | 'warning' | 'danger';
  created_at: string;
  updated_at: string;

  [key: string]: unknown;
}
