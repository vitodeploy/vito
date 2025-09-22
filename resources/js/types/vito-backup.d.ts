export interface VitoBackup {
  id: number;
  name: string;
  frequency: string;
  keep_backups: number;
  status: string;
  storage_id: number;
  storage?: {
    id: number;
    profile: string;
    provider: string;
  };
  files?: VitoBackupFile[];
  created_at: string;
  updated_at: string;
}

export interface VitoBackupFile {
  id: number;
  name: string;
  size: number;
  status: string;
  path?: string;
  created_at: string;
  updated_at: string;
}
