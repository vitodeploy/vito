import { useEffect, useRef, useState } from 'react';
import { AvailableSsl } from '@/types/hosted-domain';
import axios from 'axios';

type SslFormFields = { ssl_method: string; ssl_id: string };

interface UseSslMatchingOptions {
  serverId: number;
  siteId: number;
  domain: string;
  sslId: string;
  applySslSettings: (settings: SslFormFields) => void;
  open: boolean;
  originalDomain?: string;
}

export function useSslMatching({ serverId, siteId, domain, sslId, applySslSettings, open, originalDomain }: UseSslMatchingOptions) {
  const [matchingSsls, setMatchingSsls] = useState<AvailableSsl[]>([]);
  const [loadingSsls, setLoadingSsls] = useState(false);
  const lastFetchedDomain = useRef(originalDomain ?? '');
  const applySslSettingsRef = useRef(applySslSettings);
  applySslSettingsRef.current = applySslSettings;

  const sslStale = domain !== lastFetchedDomain.current;

  useEffect(() => {
    if (!open) {
      return;
    }

    if (!domain) {
      setMatchingSsls([]);
      lastFetchedDomain.current = domain;
      return;
    }

    const controller = new AbortController();
    const timeoutId = setTimeout(() => {
      setLoadingSsls(true);
      axios
        .get(route('hosted-domains.matching-ssls', { server: serverId, site: siteId, domain }), { signal: controller.signal })
        .then((response) => {
          const { certificates, best_match_id } = response.data;
          setMatchingSsls(certificates);
          lastFetchedDomain.current = domain;
          if (originalDomain && domain === originalDomain) {
            return;
          }
          if (best_match_id) {
            applySslSettingsRef.current({ ssl_method: 'custom', ssl_id: String(best_match_id) });
          } else {
            applySslSettingsRef.current({ ssl_method: 'letsencrypt', ssl_id: '' });
          }
        })
        .catch((error) => {
          if (!axios.isCancel(error)) {
            setMatchingSsls([]);
            lastFetchedDomain.current = domain;
          }
        })
        .finally(() => {
          setLoadingSsls(false);
        });
    }, 500);

    return () => {
      clearTimeout(timeoutId);
      controller.abort();
    };
  }, [domain, open, serverId, siteId, originalDomain]);

  const handleSslMethodChange = (value: string) => {
    applySslSettings({ ssl_method: value, ssl_id: value !== 'custom' ? '' : sslId });
  };

  const reset = (resetDomain?: string) => {
    setMatchingSsls([]);
    lastFetchedDomain.current = resetDomain ?? '';
  };

  return { matchingSsls, loadingSsls, sslStale, handleSslMethodChange, reset };
}
