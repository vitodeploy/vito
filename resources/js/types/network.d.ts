export type StatusColor = 'gray' | 'success' | 'info' | 'warning' | 'danger';

export interface Network {
  id: number;
  project_id: number;
  name: string;
  type: string;
  type_value: string;
  type_color: StatusColor;
  addressing_pool: string;
  cidr: string | null;
  port: number | null;
  status: string;
  status_color: StatusColor;
  servers_count?: number;
  created_at: string;
  updated_at: string;
}

export interface NetworkServer {
  id: number;
  network_id: number;
  server_id: number;
  server_name?: string;
  ip: string | null;
  private_ip?: string | null;
  public_key: string | null;
  status: string;
  status_color: StatusColor;
  created_at: string;
  updated_at: string;
}

export interface NetworkFirewallRule {
  id: number;
  network_id: number;
  name: string;
  protocol: string | null;
  port: string | null;
  status: string;
  status_color: StatusColor;
  created_at: string;
  updated_at: string;
}

export interface NetworkServerOption {
  id: number;
  name: string;
  is_ready: boolean;
  private_ips: { id: number; ip: string; is_primary: boolean }[];
}

export interface NetworkMemberIp {
  id: number;
  server_id: number;
  server_name: string;
  ip_address_id: number | null;
  private_ips: { id: number; ip: string; is_primary: boolean }[];
}
