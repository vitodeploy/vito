import { useCallback, useEffect, useRef, useState } from 'react';
import axios from 'axios';
import { useSocketListener } from '@/hooks/use-socket-events';

type UseLogContentOptions = {
  /** The server ID owning the log */
  serverId: number;
  /** The log ID to fetch and subscribe to */
  logId: number;
  /** Whether fetching is enabled (e.g., dialog is open) */
  enabled?: boolean;
};

type UseLogContentReturn = {
  content: string;
  isLoading: boolean;
  error: string | null;
};

/**
 * Reusable hook for streaming log content via socket events.
 *
 * Fetches the initial log content via HTTP, then listens for
 * `server-log.content` socket events to append new content in realtime.
 */
export function useLogContent({ serverId, logId, enabled = true }: UseLogContentOptions): UseLogContentReturn {
  const [content, setContent] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const logIdRef = useRef(logId);
  logIdRef.current = logId;

  // Fetch initial content when enabled
  useEffect(() => {
    if (!enabled || !logId) {
      return;
    }

    setIsLoading(true);
    setError(null);
    setContent('');

    axios
      .get(route('logs.show', { server: serverId, log: logId }))
      .then((response) => {
        const data = typeof response.data === 'string' ? response.data : JSON.stringify(response.data, null, 2);
        setContent(data);
      })
      .catch((err: unknown) => {
        if (axios.isAxiosError(err)) {
          setError(err.response?.data?.error || 'An error occurred while fetching the log');
        } else {
          setError('Unknown error occurred');
        }
      })
      .finally(() => {
        setIsLoading(false);
      });
  }, [enabled, serverId, logId]);

  // Listen for realtime content appends
  useSocketListener(
    useCallback(
      (event) => {
        if (event.type !== 'server-log.content') return;
        if (!event.data || (event.data as { id?: number }).id !== logIdRef.current) return;

        const buf = (event.data as { content?: string }).content;
        if (buf) {
          setContent((prev) => prev + buf);
        }
      },
      [], // logIdRef is a ref, no need to depend on it
    ),
  );

  return { content, isLoading, error };
}
