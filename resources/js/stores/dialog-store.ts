import { create } from 'zustand';
import type { DialogRegistry, ConsumerProps } from '@/components/dialogs/registry';

export type ActiveDialog = {
  [K in keyof DialogRegistry]: { key: K; props: ConsumerProps<DialogRegistry[K]> };
}[keyof DialogRegistry];

type DialogStore = {
  active: ActiveDialog | null;
  instanceId: number;
  nested: ActiveDialog | null;
  nestedId: number;
  open: <K extends keyof DialogRegistry>(key: K, props: ConsumerProps<DialogRegistry[K]>) => void;
  openNested: <K extends keyof DialogRegistry>(key: K, props: ConsumerProps<DialogRegistry[K]>) => void;
  close: () => void;
  closeNested: () => void;
};

let triggerElement: HTMLElement | null = null;
let nestedTriggerElement: HTMLElement | null = null;

function focusedElement(): HTMLElement | null {
  return document.activeElement instanceof HTMLElement ? document.activeElement : null;
}

function restoreFocus(trigger: HTMLElement | null, isCurrent: () => boolean) {
  requestAnimationFrame(() => {
    if (isCurrent() && trigger?.isConnected) {
      trigger.focus();
    }
  });
}

export const useDialogStore = create<DialogStore>((set, get) => ({
  active: null,
  instanceId: 0,
  nested: null,
  nestedId: 0,
  open: (key, props) => {
    triggerElement = focusedElement();
    nestedTriggerElement = null;
    set({ active: { key, props } as ActiveDialog, instanceId: get().instanceId + 1, nested: null });
  },
  openNested: (key, props) => {
    nestedTriggerElement = focusedElement();
    set({ nested: { key, props } as ActiveDialog, nestedId: get().nestedId + 1 });
  },
  close: () => {
    const trigger = triggerElement;
    const generation = get().instanceId;
    triggerElement = null;
    nestedTriggerElement = null;
    set({ active: null, nested: null });
    restoreFocus(trigger, () => get().instanceId === generation);
  },
  closeNested: () => {
    const trigger = nestedTriggerElement;
    const generation = get().nestedId;
    nestedTriggerElement = null;
    set({ nested: null });
    restoreFocus(trigger, () => get().nestedId === generation);
  },
}));
