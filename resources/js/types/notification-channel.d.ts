export interface NotificationChannel {
  id: number;
  project_id?: number;
  global: boolean;
  name: string;
  provider: string;
  created_at: string;
  updated_at: string;

  [key: string]: unknown;
}
