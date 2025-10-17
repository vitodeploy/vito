import { DNSProvider } from './dns-provider';

export interface Domain {
  id: number;
  domain: string;
  dns_provider_id: number;
  dns_provider?: DNSProvider;
  metadata: Record<string, string>;
  created_at: string;
  updated_at: string;
}
