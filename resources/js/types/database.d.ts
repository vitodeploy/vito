export interface Database {
  id: number;
  server_id: number;
  name: string;
  collation: string;
  charset: string;
  status: string;
  status_color: string;
  created_at: string;
  updated_at: string;

  [key: string]: unknown;
}
