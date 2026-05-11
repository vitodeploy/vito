export interface SourceControl {
  id: number;
  project_id?: number;
  global: boolean;
  name: string;
  provider: string;
  url?: string;
  port?: number;
  created_at: string;
  updated_at: string;

  [key: string]: unknown;
}
