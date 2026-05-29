import { useEffect } from 'react';
import type { ComponentType } from 'react';
import { router } from '@inertiajs/react';
import { useDialogStore } from '@/stores/dialog-store';
import { dialogs, type DialogControlProps } from './registry';

export default function DialogHost() {
  const active = useDialogStore((s) => s.active);

  useEffect(() => {
    return router.on('start', () => useDialogStore.getState().close());
  }, []);

  if (!active) {
    return null;
  }

  const Component = dialogs[active.key] as ComponentType<typeof active.props & DialogControlProps> | undefined;

  if (!Component) {
    return null;
  }

  return (
    <Component
      key={active.key}
      open
      onOpenChange={(o: boolean) => !o && useDialogStore.getState().close()}
      {...active.props}
    />
  );
}
