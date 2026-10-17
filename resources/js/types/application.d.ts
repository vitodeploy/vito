import { Configs } from '@/types';
import { DynamicFieldValue } from '@/types/dynamic-field-config';

export interface Application {
  id: number;
  project_id: number;
  source_control_id: number | null;
  name: string;
  repository: string;
  runtime: string;
  created_at: string;
  updated_at: string;
}

export interface Environment {
  id: number;
  application_id: number;
  name: string;
  slug: string;
  branch: string;
  runtime_version: string | null;
  status: string;
  status_color: 'success' | 'danger';
  created_at: string;
  updated_at: string;
}

export type RuntimePreset = Configs['application']['runtimes'][number];

export type EnvironmentFormData = {
  name: string;
  branch: string;
  runtime_version: string;
  [key: string]: DynamicFieldValue;
};
