export interface ServerLog {
  id: number;
  server_id: number;
  server_name?: string;
  site_id: number | null;
  network_id?: number | null;
  type: string;
  name: string;
  disk: string;
  is_remote: boolean;
  created_at: string;
  updated_at: string;

  [key: string]: unknown;
}
