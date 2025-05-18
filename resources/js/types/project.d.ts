import { User } from '@/types/user';

export interface Project {
  id: number;
  name: string;
  users: User[];
  created_at: string;
  updated_at: string;
  [key: string]: unknown;
}
