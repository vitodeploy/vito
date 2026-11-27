import { DynamicFieldConfig, DynamicFieldValue } from './dynamic-field-config';

export type WorkflowInputs = Record<string, DynamicFieldValue>;

export interface WorkflowAction {
  id: string;
  label: string;
  description?: string;
  starting?: boolean;
  category?: string;
  handler: string;
  fields?: DynamicFieldConfig[];
  inputs?: WorkflowInputs;
  outputs?: {
    [key: string]: string;
  };
}

export interface WorkflowVariable {
  key: string;
  description?: string;
}
