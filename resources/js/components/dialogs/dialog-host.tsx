import { useEffect } from 'react';
import type { ComponentType } from 'react';
import { router } from '@inertiajs/react';
import { useDialogStore, type ActiveDialog } from '@/stores/dialog-store';
import { dialogs, type DialogControlProps } from './registry';

function HostedDialog({ dialog, id, onClose }: { dialog: ActiveDialog | null; id: number; onClose: () => void }) {
  if (!dialog) {
    return null;
  }

  const Component = dialogs[dialog.key] as ComponentType<typeof dialog.props & DialogControlProps> | undefined;

  if (!Component) {
    return null;
  }

  return <Component key={`${dialog.key}:${id}`} open onOpenChange={(o: boolean) => !o && onClose()} {...dialog.props} />;
}

export default function DialogHost() {
  const active = useDialogStore((s) => s.active);
  const instanceId = useDialogStore((s) => s.instanceId);
  const nested = useDialogStore((s) => s.nested);
  const nestedId = useDialogStore((s) => s.nestedId);

  useEffect(() => {
    return router.on('navigate', () => useDialogStore.getState().close());
  }, []);

  return (
    <>
      <HostedDialog dialog={active} id={instanceId} onClose={() => useDialogStore.getState().close()} />
      <HostedDialog dialog={nested} id={nestedId} onClose={() => useDialogStore.getState().closeNested()} />
    </>
  );
}
