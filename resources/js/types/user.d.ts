import { Project } from '@/types/project';

export interface User {
  id: number;
  name: string;
  email: string;
  avatar?: string;
  email_verified_at: string | null;
  created_at: string;
  updated_at: string;
  timezone: string;
  projects?: Project[];
  two_factor_enabled: boolean;
  role: string;
  [key: string]: unknown; // This allows for additional properties...
}
