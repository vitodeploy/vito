import { create } from 'zustand';
import type { Edge, Node } from '@xyflow/react';

type WorkflowClipboardStore = {
  projectId: number | null;
  nodes: Node[];
  edges: Edge[];
  set: (projectId: number, nodes: Node[], edges: Edge[]) => void;
};

export const useWorkflowClipboard = create<WorkflowClipboardStore>((set) => ({
  projectId: null,
  nodes: [],
  edges: [],
  set: (projectId, nodes, edges) => set({ projectId, nodes, edges }),
}));
