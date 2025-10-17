import { Domain } from './domain';

export interface DNSRecord {
  id: number;
  type: string;
  name: string;
  formatted_name: string;
  content: string;
  ttl: number;
  formatted_ttl: string;
  proxied: boolean;
  domain_id: number;
  domain?: Domain;
  created_at: string;
  updated_at: string;
}
