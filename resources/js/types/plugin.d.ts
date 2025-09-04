export interface LegacyPlugin {
  name: string;
  version: string;
}

export interface Plugin {
  name: string;
  id: number;
  version: string;
  repo: string;
  is_enabled: boolean;
  is_installed: boolean;
  folder: string;
  error_count: number;
  updates_available: boolean;
}
