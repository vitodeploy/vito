export interface DNSProvider {
  id: number;
  name: string;
  provider: string;
  connected: boolean;
  project_id: number | null;
  global: boolean;
  editable_data: Record<string, string>;
  created_at: string;
  updated_at: string;
}
