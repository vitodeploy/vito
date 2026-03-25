import { registerTableHook, registerIcons } from '@forjedio/inertia-table-react';
import { SOCKET_EVENT } from '@/stores/socket-store';
import type { SocketEventData } from '@/stores/socket-store';
import { CrownIcon, CopyIcon, SignpostIcon } from 'lucide-react';

registerIcons({
  crown: CrownIcon,
  copy: CopyIcon,
  signpost: SignpostIcon,
});

registerTableHook('realtime', ({ value, refresh }) => {
  const prefix = value as string;
  const handler = (e: CustomEvent<SocketEventData>) => {
    const { type } = e.detail;
    if (type?.startsWith(`${prefix}.`)) {
      refresh();
    }
  };
  window.addEventListener(SOCKET_EVENT, handler);
  return () => window.removeEventListener(SOCKET_EVENT, handler);
});
