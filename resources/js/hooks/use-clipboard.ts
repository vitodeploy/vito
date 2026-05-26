import { useState, useCallback, useRef, useEffect } from 'react';
import { toast } from 'sonner';

export function useClipboard(timeout = 2000) {
  const [copied, setCopied] = useState(false);
  const timeoutRef = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(() => {
    return () => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
      }
    };
  }, []);

  const copy = useCallback(
    (text: string) => {
      if (timeoutRef.current) {
        clearTimeout(timeoutRef.current);
      }

      navigator.clipboard
        .writeText(text)
        .then(() => {
          setCopied(true);
          toast.success('Copied to clipboard!');
          timeoutRef.current = setTimeout(() => {
            setCopied(false);
          }, timeout);
        })
        .catch(() => {
          toast.error('Failed to copy to clipboard');
        });
    },
    [timeout],
  );

  return { copied, copy };
}
