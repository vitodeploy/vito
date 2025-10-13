export interface WorkflowAction {
  id: string;
  label: string;
  description?: string;
  starting?: boolean;
  category?: string;
  handler: string;
  inputs?: {
    [key: string]: string;
  };
  outputs?: {
    [key: string]: string;
  };
}
