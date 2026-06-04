export type SecurityStatusColor = 'gray' | 'success' | 'info' | 'warning' | 'danger';

export interface SecurityCheck {
  key: string;
  label: string;
  passed: boolean;
}

export interface SecurityScore {
  score: number;
  passed: number;
  total: number;
  checks: SecurityCheck[];
}

export interface AutoUpdateState {
  enabled: boolean;
  schedule?: string | null;
}

export interface PasswordAuthState {
  enabled: boolean;
  detected?: boolean | null;
  status: string;
  status_color: SecurityStatusColor;
}

export interface RootLoginState {
  enabled: boolean;
  detected?: boolean | null;
  status: string;
  status_color: SecurityStatusColor;
  manageable: boolean;
}
