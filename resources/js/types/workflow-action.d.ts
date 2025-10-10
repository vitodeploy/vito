import { DynamicFieldConfig } from './dynamic-field-config';

export interface WorkflowAction {
  id: string;
  label: string;
  description?: string;
  category?: string;
  handler: string;
  form?: DynamicFieldConfig[];
  data?: {
    [key: string]: string | number | boolean | string[];
  };
  outputs?: {
    [key: string]: string;
  };
}
