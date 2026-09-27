export interface ServerProvider {
  id: number;
  user_id: number;
  provider: string;
  name: string;
  global: boolean;
  connected: boolean;
  editable_data: Record<string, string | number | boolean | null>;
  project_id?: number;
  created_at: string;
  updated_at: string;

  [key: string]: unknown;
}
