export interface GithubAppDetails {
  account_login: string | null;
  account_type: string | null;
  html_url: string | null;
}

export interface SourceControl {
  id: number;
  project_id?: number;
  global: boolean;
  name: string;
  provider: string;
  external_identifier?: string | null;
  github_app?: GithubAppDetails;
  ssh_port?: number;
  created_at: string;
  updated_at: string;

  [key: string]: unknown;
}
