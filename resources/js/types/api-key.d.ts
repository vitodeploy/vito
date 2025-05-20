export interface ApiKey {
  id: number;
  name: string;
  permissions: string[];
  created_at: string;
  updated_at: string;

  [key: string]: unknown;
}
