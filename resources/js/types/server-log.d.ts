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

  [key: string]: unknown;
}
