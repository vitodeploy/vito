import { Dispatch, SetStateAction, useCallback, useEffect, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { PaginatedData, SharedData } from '@/types';
import { useSocketStore, SOCKET_EVENT } from '@/stores/socket-store';
import type { SocketEventData, SocketStatus } from '@/stores/socket-store';

export type { SocketEventData, SocketStatus, WebSocketMessage } from '@/stores/socket-store';
export { SOCKET_EVENT } from '@/stores/socket-store';

export function useSocketEvents(): { status: SocketStatus; reconnect: () => void } {
  const { auth, csrf_token } = usePage<SharedData>().props;
  const csrfRef = useRef(csrf_token);
  csrfRef.current = csrf_token;

  const status = useSocketStore((s) => s.status);
  const connect = useSocketStore((s) => s.connect);
  const disconnect = useSocketStore((s) => s.disconnect);
  const storeReconnect = useSocketStore((s) => s.reconnect);
  const switchProject = useSocketStore((s) => s.switchProject);

  const projectId = auth?.currentProject?.id;

  useEffect(() => {
    if (auth && csrfRef.current) {
      connect(csrfRef.current);
    }
  }, [auth, connect]);

  useEffect(() => {
    if (projectId) {
      switchProject(projectId);
    }
  }, [projectId, switchProject]);

  useEffect(() => {
    if (!auth) {
      disconnect();
    }
  }, [auth, disconnect]);

  const reconnect = useCallback(() => {
    if (csrfRef.current) {
      storeReconnect(csrfRef.current);
    }
  }, [storeReconnect]);

  return { status, reconnect };
}

/**
 * Listen for socket events on a page. The callback receives the event data
 * and can decide whether to handle it or ignore it.
 */
export function useSocketListener(callback: (data: SocketEventData) => void): void {
  const callbackRef = useRef(callback);
  callbackRef.current = callback;

  useEffect(() => {
    const handler = (e: CustomEvent<SocketEventData>) => {
      callbackRef.current(e.detail);
    };
    window.addEventListener(SOCKET_EVENT, handler);
    return () => window.removeEventListener(SOCKET_EVENT, handler);
  }, []);
}

/**
 * Manages paginated Inertia data with realtime socket updates.
 *
 * Listens for socket events matching `{eventPrefix}.updated` (replace row),
 * and `{eventPrefix}.deleted` (remove row) automatically.
 *
 * Returns the live data and setter for custom handling.
 */
export function useRealtime<T extends { id: number }>(
  initialData: PaginatedData<T>,
  eventPrefix: string,
): [PaginatedData<T>, Dispatch<SetStateAction<PaginatedData<T>>>] {
  const [data, setData] = useState<PaginatedData<T>>(initialData);

  useEffect(() => {
    setData(initialData);
  }, [initialData]);

  useSocketListener(
    useCallback(
      (event) => {
        const { type, data: eventData } = event;
        if (!type?.startsWith(`${eventPrefix}.`) || !eventData || typeof eventData !== 'object' || !('id' in eventData)) {
          return;
        }

        const action = type.slice(eventPrefix.length + 1);

        switch (action) {
          case 'created':
            setData((prev) => ({
              ...prev,
              data: [eventData as unknown as T, ...prev.data],
            }));
            break;
          case 'updated':
            setData((prev) => ({
              ...prev,
              data: prev.data.map((item) => (item.id === (eventData as unknown as T).id ? (eventData as unknown as T) : item)),
            }));
            break;
          case 'deleted':
            setData((prev) => ({
              ...prev,
              data: prev.data.filter((item) => item.id !== eventData.id),
            }));
            break;
        }
      },
      [eventPrefix],
    ),
  );

  return [data, setData];
}
