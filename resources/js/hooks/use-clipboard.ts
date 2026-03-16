import { useState, useCallback } from 'react';
import { toast } from 'sonner';

export function useClipboard(timeout = 2000) {
  const [copied, setCopied] = useState(false);

  const copy = useCallback(
    (text: string) => {
      navigator.clipboard.writeText(text).then(() => {
        setCopied(true);
        toast.success('Copied to clipboard!');
        setTimeout(() => {
          setCopied(false);
        }, timeout);
      });
    },
    [timeout],
  );

  return { copied, copy };
}
