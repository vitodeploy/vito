import { useMemo } from 'react';
import { useDialogStore } from '@/stores/dialog-store';
import { dialogs, type DialogRegistry, type ConsumerProps } from '@/components/dialogs/registry';

type DialogAccessor = {
  -readonly [K in keyof DialogRegistry]: {
    open: (props: ConsumerProps<DialogRegistry[K]>) => void;
    close: () => void;
  };
};

function entryFor<K extends keyof DialogRegistry>(key: K) {
  return {
    open: (props: ConsumerProps<DialogRegistry[K]>) => useDialogStore.getState().open(key, props),
    close: () => useDialogStore.getState().close(),
  };
}

export function useDialog(): DialogAccessor {
  return useMemo(() => {
    const accessor = {} as DialogAccessor;
    (Object.keys(dialogs) as Array<keyof DialogRegistry>).forEach((key) => {
      (accessor as Record<string, unknown>)[key] = entryFor(key);
    });
    return accessor;
  }, []);
}
