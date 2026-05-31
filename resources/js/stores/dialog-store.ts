import { create } from 'zustand';
import type { DialogRegistry, ConsumerProps } from '@/components/dialogs/registry';

export type ActiveDialog = {
  [K in keyof DialogRegistry]: { key: K; props: ConsumerProps<DialogRegistry[K]> };
}[keyof DialogRegistry];

type DialogStore = {
  active: ActiveDialog | null;
  instanceId: number;
  open: <K extends keyof DialogRegistry>(key: K, props: ConsumerProps<DialogRegistry[K]>) => void;
  close: () => void;
};

let triggerElement: HTMLElement | null = null;

export const useDialogStore = create<DialogStore>((set, get) => ({
  active: null,
  instanceId: 0,
  open: (key, props) => {
    triggerElement = document.activeElement instanceof HTMLElement ? document.activeElement : null;
    set({ active: { key, props } as ActiveDialog, instanceId: get().instanceId + 1 });
  },
  close: () => {
    const trigger = triggerElement;
    const generation = get().instanceId;
    triggerElement = null;
    set({ active: null });
    requestAnimationFrame(() => {
      if (get().instanceId === generation && trigger?.isConnected) {
        trigger.focus();
      }
    });
  },
}));
