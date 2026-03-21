import { useCallback, useEffect, useRef, useState } from 'react';
import { AvailableSsl } from '@/types/hosted-domain';
import axios from 'axios';

type SetData = (callback: (prev: Record<string, string>) => Record<string, string>) => void;

interface UseSslMatchingOptions {
  serverId: number;
  siteId: number;
  domain: string;
  sslMethod: string;
  setData: SetData;
  open: boolean;
  originalDomain?: string;
}

export function useSslMatching({ serverId, siteId, domain, sslMethod, setData, open, originalDomain }: UseSslMatchingOptions) {
  const [matchingSsls, setMatchingSsls] = useState<AvailableSsl[]>([]);
  const [loadingSsls, setLoadingSsls] = useState(false);
  const lastFetchedDomain = useRef(originalDomain ?? '');

  const sslStale = domain !== lastFetchedDomain.current;

  const fetchMatchingSsls = useCallback(
    (domainToFetch: string, signal?: AbortSignal) => {
      if (!domainToFetch) {
        setMatchingSsls([]);
        lastFetchedDomain.current = domainToFetch;
        return;
      }

      setLoadingSsls(true);
      axios
        .get(route('hosted-domains.matching-ssls', { server: serverId, site: siteId, domain: domainToFetch }), { signal })
        .then((response) => {
          const { certificates, best_match_id } = response.data;
          setMatchingSsls(certificates);
          lastFetchedDomain.current = domainToFetch;
          if (originalDomain && domainToFetch === originalDomain) {
            return;
          }
          if (best_match_id) {
            setData((prev) => ({ ...prev, ssl_method: 'custom', ssl_id: String(best_match_id) }));
          } else {
            setData((prev) => ({ ...prev, ssl_method: 'letsencrypt', ssl_id: '' }));
          }
        })
        .catch((error) => {
          if (!axios.isCancel(error)) {
            setMatchingSsls([]);
            lastFetchedDomain.current = domainToFetch;
          }
        })
        .finally(() => {
          setLoadingSsls(false);
        });
    },
    [serverId, siteId, originalDomain, setData],
  );

  useEffect(() => {
    if (!open) {
      return;
    }

    if (sslStale && sslMethod === 'custom') {
      setData((prev) => ({ ...prev, ssl_method: 'letsencrypt', ssl_id: '' }));
    }

    const controller = new AbortController();
    const timeoutId = setTimeout(() => {
      fetchMatchingSsls(domain, controller.signal);
    }, 500);

    return () => {
      clearTimeout(timeoutId);
      controller.abort();
    };
  }, [domain, open, fetchMatchingSsls]);

  const handleSslMethodChange = (value: string) => {
    setData((prev) => ({ ...prev, ssl_method: value, ssl_id: value !== 'custom' ? '' : prev.ssl_id }));
  };

  const reset = (resetDomain?: string) => {
    setMatchingSsls([]);
    lastFetchedDomain.current = resetDomain ?? '';
  };

  return { matchingSsls, loadingSsls, sslStale, handleSslMethodChange, reset };
}
