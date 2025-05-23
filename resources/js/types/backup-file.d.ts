export interface BackupFile {
  id: number;
  backup_id: number;
  server_id: number;
  name: string;
  size: number;
  restored_to: string;
  restored_at: string;
  status: string;
  status_color: 'gray' | 'success' | 'info' | 'warning' | 'danger';
  created_at: string;
  updated_at: string;
  [key: string]: unknown;
}
