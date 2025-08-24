import { ServerLog } from '@/types/server-log';

export interface Deployment {
  id: number;
  site_id: number;
  deployment_script_id: number;
  log_id: number;
  log: ServerLog;
  commit_id: string;
  commit_id_short: string;
  commit_data: {
    name?: string;
    email?: string;
    message?: string;
    url?: string;
  };
  status: string;
  status_color: 'gray' | 'success' | 'info' | 'warning' | 'danger';
  release?: string;
  active: boolean;
  created_at: string;
  updated_at: string;

  [key: string]: unknown;
}
