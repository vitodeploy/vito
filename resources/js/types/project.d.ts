import { User } from '@/types/user';

export interface Project {
  id: number;
  name: string;
  users: User[];
  created_at: string;
  updated_at: string;
  created_at_by_timezone: string;
  updated_at_by_timezone: string;
  [key: string]: unknown;
}
