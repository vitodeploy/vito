export interface DeploymentScriptConfigs {
  restart_workers: boolean;
}

export interface DeploymentScript {
  id: number;
  site_id: number;
  name: string;
  content: string;
  configs: DeploymentScriptConfigs;
  created_at: string;
  updated_at: string;

  [key: string]: unknown;
}
