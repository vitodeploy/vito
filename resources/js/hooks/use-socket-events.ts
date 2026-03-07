import { Dispatch, SetStateAction, useCallback, useEffect, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { PaginatedData, SharedData } from '@/types';

export type SocketEventData = {
  project_id: number;
  type: string;
  data: Record<string, unknown>;
};

export const SOCKET_EVENT = 'vito:socket-event' as const;

declare global {
  interface WindowEventMap {
    [SOCKET_EVENT]: CustomEvent<SocketEventData>;
  }
}

type WebSocketMessage =
  | { type: 'connected'; project_id: number }
  | { type: 'subscribed'; project_id: number }
  | { type: 'event'; data: SocketEventData }
  | { type: 'error'; message: string };

const RECONNECT_BASE_DELAY = 1000;
const RECONNECT_MAX_DELAY = 30000;

async function requestEventsToken(csrfToken: string): Promise<{ token: string; url: string } | null> {
  try {
    const response = await fetch(route('events.token'), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': csrfToken,
      },
    });
    if (!response.ok) return null;
    return await response.json();
  } catch {
    return null;
  }
}

export function useSocketEvents(): void {
  const { auth, csrf_token } = usePage<SharedData>().props;
  const authRef = useRef(auth);
  const csrfRef = useRef(csrf_token);
  authRef.current = auth;
  csrfRef.current = csrf_token;

  const wsRef = useRef<WebSocket | null>(null);
  const reconnectAttemptRef = useRef(0);
  const reconnectTimerRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const currentProjectIdRef = useRef<number | null>(null);

  const cleanup = useCallback(() => {
    if (reconnectTimerRef.current) {
      clearTimeout(reconnectTimerRef.current);
      reconnectTimerRef.current = null;
    }
    if (wsRef.current) {
      wsRef.current.onclose = null;
      wsRef.current.onerror = null;
      wsRef.current.onmessage = null;
      wsRef.current.close();
      wsRef.current = null;
    }
  }, []);

  const scheduleReconnect = useCallback((connectFn: () => void) => {
    const delay = Math.min(RECONNECT_BASE_DELAY * Math.pow(2, reconnectAttemptRef.current), RECONNECT_MAX_DELAY);
    reconnectAttemptRef.current++;
    reconnectTimerRef.current = setTimeout(connectFn, delay);
  }, []);

  const connect = useCallback(async () => {
    if (!authRef.current || !csrfRef.current) {
      return;
    }

    cleanup();

    const tokenData = await requestEventsToken(csrfRef.current);
    if (!tokenData) {
      scheduleReconnect(() => connect());
      return;
    }

    const ws = new WebSocket(`${tokenData.url}?token=${tokenData.token}`);
    wsRef.current = ws;

    ws.onmessage = (event) => {
      try {
        const msg: WebSocketMessage = JSON.parse(event.data);

        switch (msg.type) {
          case 'connected':
            reconnectAttemptRef.current = 0;
            currentProjectIdRef.current = msg.project_id;
            break;

          case 'subscribed':
            currentProjectIdRef.current = msg.project_id;
            break;

          case 'event':
            window.dispatchEvent(new CustomEvent(SOCKET_EVENT, { detail: msg.data }));
            break;

          case 'error':
            console.warn('[WS Events]', msg.message);
            break;
        }
      } catch {
        // ignore non-JSON messages
      }
    };

    ws.onclose = () => {
      wsRef.current = null;
      scheduleReconnect(() => connect());
    };

    ws.onerror = () => {
      // onclose will fire after onerror, reconnection handled there
    };
  }, [cleanup, scheduleReconnect]);

  // Switch project subscription when the current project changes
  const projectId = auth?.currentProject?.id;

  useEffect(() => {
    if (wsRef.current?.readyState === WebSocket.OPEN && projectId && projectId !== currentProjectIdRef.current) {
      wsRef.current.send(JSON.stringify({ type: 'subscribe', project_id: projectId }));
    }
  }, [projectId]);

  // Connect on mount, cleanup on unmount
  useEffect(() => {
    connect();

    return cleanup;
  }, [connect, cleanup]);
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
