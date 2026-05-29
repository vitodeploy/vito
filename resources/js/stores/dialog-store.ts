import { create } from 'zustand';
import type { DialogRegistry, ConsumerProps } from '@/components/dialogs/registry';

export type ActiveDialog = {
  [K in keyof DialogRegistry]: { key: K; props: ConsumerProps<DialogRegistry[K]> };
}[keyof DialogRegistry];

type DialogStore = {
  active: ActiveDialog | null;
  open: <K extends keyof DialogRegistry>(key: K, props: ConsumerProps<DialogRegistry[K]>) => void;
  close: () => void;
};

export const useDialogStore = create<DialogStore>((set) => ({
  active: null,
  open: (key, props) =>
    // TS cannot narrow the mapped-type union from correlated generics —
    // `key` and `props` come from the same call so the cast is safe.
    set({ active: { key, props } as ActiveDialog }),
  close: () => set({ active: null }),
}));
