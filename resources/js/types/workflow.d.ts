import { Edge, Node } from '@xyflow/react';

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
  created_at: string;
  updated_at: string;
  [key: string]: unknown;
}
