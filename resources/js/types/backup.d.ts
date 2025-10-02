import { BackupFile } from '@/types/backup-file';
import { StorageProvider } from '@/types/storage-provider';
import { Database } from '@/types/database';

export interface Backup {
  id: number;
  server_id: number;
  storage_id: number;
  storage: StorageProvider;
  database_id: number | null;
  database: Database | null;
  path: string | null;
  type: string;
  keep_backups: number;
  interval: string;
  files_count: number;
  status: string;
  status_color: 'gray' | 'success' | 'info' | 'warning' | 'danger';
  created_at: string;
  updated_at: string;
  last_file?: BackupFile;
  [key: string]: unknown;
}
