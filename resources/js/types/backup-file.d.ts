import { Backup } from '@/types/backup';

export interface BackupFile {
  id: number;
  backup_id: number;
  backup: Backup;
  server_id: number;
  name: string;
  size: number | null;
  database_engine: string | null;
  database_version: string | null;
  restored_to: string | null;
  restored_at: string | null;
  status: string;
  message: string | null;
  status_color: 'gray' | 'success' | 'info' | 'warning' | 'danger';
  created_at: string;
  updated_at: string;
  [key: string]: unknown;
}
