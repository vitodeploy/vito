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
 * Keeps a single resource in sync with socket events.
 *
 * Returns the live resource (initially from Inertia props, updated via socket).
 * Pass `null` to skip listening (safe to call unconditionally).
 */
export function useRealtimeRecord<T extends { id: number }>(initial: T | null | undefined, eventPrefix: string): T | null {
  const [record, setRecord] = useState<T | null>(initial ?? null);

  useEffect(() => {
    setRecord(initial ?? null);
  }, [initial]);

  useSocketListener(
    useCallback(
      (event) => {
        if (!initial) return;
        if (event.type === `${eventPrefix}.updated` && event.data && 'id' in event.data && event.data.id === initial.id) {
          setRecord(event.data as unknown as T);
        }
      },
      [eventPrefix, initial?.id],
    ),
  );

  return record;
}

/**
 * Manages paginated Inertia data with realtime socket updates.
 *
 * Listens for socket events matching `{eventPrefix}.created` (prepend row),
 * `{eventPrefix}.updated` (replace row), and `{eventPrefix}.deleted` (remove row) automatically.
 *
 * Socket events are broadcast project-wide, so pass a `scope` of field/value pairs
 * (e.g. `{ server_id: server.id }`) that created records must match before trigger refreshes
 *
 * Returns the live data and setter for custom handling.
 */
export function useRealtime<T extends { id: number }>(
  initialData: PaginatedData<T>,
  eventPrefix: string,
  scope?: Partial<T>,
): [PaginatedData<T>, Dispatch<SetStateAction<PaginatedData<T>>>] {
  const [data, setData] = useState<PaginatedData<T>>(initialData);
  const scopeRef = useRef(scope);
  scopeRef.current = scope;

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
            if (!Object.entries(scopeRef.current ?? {}).every(([key, value]) => (eventData as Record<string, unknown>)[key] === value)) {
              break;
            }
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
