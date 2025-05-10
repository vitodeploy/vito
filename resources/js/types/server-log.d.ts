export interface ServerLog {
  id: number;
  server_id: number;
  site_id?: number;
  type: string;
  name: string;
  disk: string;
  is_remote: boolean;
  created_at: string;
  updated_at: string;
  created_at_by_timezone: string;
  updated_at_by_timezone: string;

  [key: string]: unknown;
}
