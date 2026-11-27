import { createContext, useContext } from 'react';
import { WorkflowAction } from '@/types/workflow-action';

export type WorkflowEditor = {
  actionFor: (handler: string) => WorkflowAction | undefined;
  editNode: (id: string) => void;
  copyNodes: (ids?: string[]) => boolean;
  duplicateNodes: (ids?: string[]) => boolean;
  setStartingNode: (id: string) => void;
};

export const WorkflowEditorContext = createContext<WorkflowEditor | null>(null);

export function useWorkflowEditor(): WorkflowEditor {
  const editor = useContext(WorkflowEditorContext);
  if (!editor) {
    throw new Error('useWorkflowEditor must be used inside WorkflowEditorContext');
  }
  return editor;
}
