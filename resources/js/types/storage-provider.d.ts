export interface StorageProvider {
  id: number;
  project_id?: number;
  global: boolean;
  name: string;
  provider: string;
  editable_data: Record<string, string | number | boolean>;
  created_at: string;
  updated_at: string;

  [key: string]: unknown;
}
