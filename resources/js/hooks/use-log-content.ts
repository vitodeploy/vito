import { useCallback, useEffect, useRef, useState } from 'react';
import axios from 'axios';
import { useSocketListener } from '@/hooks/use-socket-events';

type UseLogContentOptions = {
  serverId: number;
  logId: number;
  enabled?: boolean;
};

type UseLogContentReturn = {
  content: string;
  isLoading: boolean;
  error: string | null;
};

export function useLogContent({ serverId, logId, enabled = true }: UseLogContentOptions): UseLogContentReturn {
  const [content, setContent] = useState('');
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const logIdRef = useRef(logId);
  logIdRef.current = logId;

  useEffect(() => {
    if (!enabled || !logId) {
      return;
    }

    setIsLoading(true);
    setError(null);
    setContent('');

    axios
      .get(`/servers/${serverId}/logs/${logId}`)
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

  useSocketListener(
    useCallback((event) => {
      if (event.type !== 'server-log.content') return;
      if (!event.data || (event.data as { id?: number }).id !== logIdRef.current) return;

      const buf = (event.data as { content?: string }).content;
      if (buf) {
        setContent((prev) => prev + buf);
      }
    }, []),
  );

  return { content, isLoading, error };
}
