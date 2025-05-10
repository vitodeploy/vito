export interface ServerProvider {
  id: number;
  user_id: number;
  name: string;
  provider: string;
  connected: boolean;
  project_id?: number;
  created_at: string;
  updated_at: string;

  [key: string]: unknown;
}
