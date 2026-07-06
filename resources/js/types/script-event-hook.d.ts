import { Server } from '@/types/server';

export interface ScriptEventHook {
  id: number;
  script_id: number;
  user_id: number;
  project_id: number;
  server_id: number;
  server?: Server;
  event: string;
  event_value: string;
  event_color: string;
  user: string;
  enabled: boolean;
  created_at: string;
  updated_at: string;

  [key: string]: unknown;
}
