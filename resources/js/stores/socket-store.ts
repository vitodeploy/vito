import { create } from 'zustand';

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

export type WebSocketMessage =
  | { type: 'connected'; project_id: number }
  | { type: 'subscribed'; project_id: number }
  | { type: 'event'; data: SocketEventData }
  | { type: 'error'; message: string };

export type SocketStatus = 'connecting' | 'connected' | 'disconnected';

const RECONNECT_BASE_DELAY = 1000;
const RECONNECT_MAX_DELAY = 30000;
const MAX_FAST_RECONNECT_ATTEMPTS = 3;
const SLOW_RECONNECT_INTERVAL = 30000;

let ws: WebSocket | null = null;
let reconnectAttempt = 0;
let reconnectTimer: ReturnType<typeof setTimeout> | null = null;
let generation = 0;
let connectedUserId: number | null = null;
let connecting = false;

type SocketStore = {
  status: SocketStatus;
  currentProjectId: number | null;
  connect: (csrfToken: string, userId: number) => Promise<void>;
  disconnect: () => void;
  reconnect: (csrfToken: string, userId: number) => void;
  switchProject: (projectId: number) => void;
};

async function requestEventsToken(csrfToken: string): Promise<{ token: string; url: string } | null> {
  try {
    const response = await fetch('/events/token', {
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

function scheduleReconnect(csrfToken: string, userId: number): void {
  if (reconnectTimer) {
    clearTimeout(reconnectTimer);
    reconnectTimer = null;
  }
  reconnectAttempt++;
  if (reconnectAttempt > MAX_FAST_RECONNECT_ATTEMPTS) {
    useSocketStore.setState({ status: 'disconnected' });
    reconnectTimer = setTimeout(() => useSocketStore.getState().connect(csrfToken, userId), SLOW_RECONNECT_INTERVAL);
    return;
  }
  useSocketStore.setState({ status: 'connecting' });
  const delay = Math.min(RECONNECT_BASE_DELAY * Math.pow(2, reconnectAttempt - 1), RECONNECT_MAX_DELAY);
  reconnectTimer = setTimeout(() => useSocketStore.getState().connect(csrfToken, userId), delay);
}

export const useSocketStore = create<SocketStore>((set, get) => ({
  status: 'connecting',
  currentProjectId: null,

  connect: async (csrfToken: string, userId: number) => {
    if (connectedUserId !== null && connectedUserId !== userId) {
      get().disconnect();
    }

    if (connecting || (ws && (ws.readyState === WebSocket.CONNECTING || ws.readyState === WebSocket.OPEN))) {
      return;
    }

    const connectGeneration = generation;
    connectedUserId = userId;
    connecting = true;
    set({ status: 'connecting' });

    const tokenData = await requestEventsToken(csrfToken);
    if (connectGeneration !== generation) {
      return;
    }
    connecting = false;
    if (!tokenData) {
      scheduleReconnect(csrfToken, userId);
      return;
    }

    const socket = new WebSocket(`${tokenData.url}?token=${tokenData.token}`);
    ws = socket;

    socket.onmessage = (event) => {
      if (ws !== socket) {
        return;
      }
      try {
        const msg: WebSocketMessage = JSON.parse(event.data);

        switch (msg.type) {
          case 'connected':
            reconnectAttempt = 0;
            set({ status: 'connected', currentProjectId: msg.project_id });
            break;

          case 'subscribed':
            set({ currentProjectId: msg.project_id });
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

    socket.onclose = () => {
      if (ws !== socket) {
        return;
      }
      ws = null;
      scheduleReconnect(csrfToken, userId);
    };

    socket.onerror = () => {
      // onclose will fire after onerror, reconnection handled there
    };
  },

  disconnect: () => {
    generation++;
    connectedUserId = null;
    connecting = false;
    reconnectAttempt = 0;
    if (reconnectTimer) {
      clearTimeout(reconnectTimer);
      reconnectTimer = null;
    }
    if (ws) {
      ws.onclose = null;
      ws.onerror = null;
      ws.onmessage = null;
      ws.close();
      ws = null;
    }
    set({ status: 'disconnected', currentProjectId: null });
  },

  reconnect: (csrfToken: string, userId: number) => {
    get().disconnect();
    get().connect(csrfToken, userId);
  },

  switchProject: (projectId: number) => {
    const { currentProjectId } = get();
    if (ws && ws.readyState === WebSocket.OPEN && projectId !== currentProjectId) {
      ws.send(JSON.stringify({ type: 'subscribe', project_id: projectId }));
    }
  },
}));
