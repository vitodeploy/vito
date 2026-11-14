import { useEffect } from 'react';
import type { ComponentType } from 'react';
import { router } from '@inertiajs/react';
import { useDialogStore, type ActiveDialog } from '@/stores/dialog-store';
import { dialogs, routeDialogs, type DialogControlProps } from './registry';

function HostedDialog({ dialog, id, onClose }: { dialog: ActiveDialog | null; id: number; onClose: (id: number) => void }) {
  if (!dialog) {
    return null;
  }

  const Component = dialogs[dialog.key] as ComponentType<typeof dialog.props & DialogControlProps> | undefined;

  if (!Component) {
    return null;
  }

  return <Component key={`${dialog.key}:${id}`} open onOpenChange={(o: boolean) => !o && onClose(id)} {...dialog.props} />;
}

export default function DialogHost() {
  const active = useDialogStore((s) => s.active);
  const instanceId = useDialogStore((s) => s.instanceId);
  const nested = useDialogStore((s) => s.nested);
  const nestedId = useDialogStore((s) => s.nestedId);

  useEffect(() => {
    return router.on('navigate', () => {
      const store = useDialogStore.getState();
      if (!store.active || !routeDialogs.includes(store.active.key)) store.close();
    });
  }, []);

  return (
    <>
      <HostedDialog
        dialog={active}
        id={instanceId}
        onClose={(id) => {
          if (useDialogStore.getState().instanceId === id) useDialogStore.getState().close();
        }}
      />
      <HostedDialog
        dialog={nested}
        id={nestedId}
        onClose={(id) => {
          if (useDialogStore.getState().nestedId === id) useDialogStore.getState().closeNested();
        }}
      />
    </>
  );
}
