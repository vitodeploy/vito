export interface ProjectUser {
  id: number;
  user_id: number;
  project_id: number;
  project_name: string;
  email: string;
  role: string;
  type: 'user' | 'invitation';
}
