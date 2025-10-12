import { Edge, Node } from '@xyflow/react';
import { DynamicFieldConfig } from './dynamic-field-config';

export interface Workflow {
  id: number;
  user_id: number;
  project_id: number;
  name: string;
  nodes: Node[];
  edges: Edge[];
  run_inputs: {
    [key: string]: string;
  };
  run_form: DynamicFieldConfig[];
  created_at: string;
  updated_at: string;
  [key: string]: unknown;
}
