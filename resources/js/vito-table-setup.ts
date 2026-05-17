import { registerTableHook, registerIcons } from 'inertia-table-react';
import { SOCKET_EVENT } from '@/stores/socket-store';
import type { SocketEventData } from '@/stores/socket-store';
import { CrownIcon, CopyIcon, SignpostIcon, DatabaseIcon } from 'lucide-react';

registerIcons({
  crown: CrownIcon,
  copy: CopyIcon,
  signpost: SignpostIcon,
  database: DatabaseIcon,
});

registerTableHook('realtime', ({ value, refresh }) => {
  const prefix = value as string;
  let timeout: ReturnType<typeof setTimeout>;

  const handler = (e: CustomEvent<SocketEventData>) => {
    const { type } = e.detail;
    if (type?.startsWith(`${prefix}.`)) {
      clearTimeout(timeout);
      timeout = setTimeout(refresh, 900);
    }
  };

  window.addEventListener(SOCKET_EVENT, handler);
  return () => {
    clearTimeout(timeout);
    window.removeEventListener(SOCKET_EVENT, handler);
  };
});
