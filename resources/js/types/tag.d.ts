export interface Tag {
  id: number;
  project_id: number;
  name: string;
  color: string;
  created_at: string;
  updated_at: string;

  [key: string]: unknown;
}
