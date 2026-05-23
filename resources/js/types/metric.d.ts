export interface Metric {
  date: string;
  load: number;
  memory_total: number;
  memory_used: number;
  memory_free: number;
  disk_total: number;
  disk_used: number;
  disk_free: number;
  cpu_usage_percent: number | null;
  cpu_steal_percent: number | null;
  memory_used_percent: number | null;
  swap_total: number | null;
  swap_used: number | null;
  swap_free: number | null;
  swap_used_percent: number | null;
  oom_kill_count: number | null;
  disk_used_percent: number | null;

  [key: string]: number | string | null;
}

export interface CurrentMetric {
  date: string;
  cpu_cores: number | null;
  cpu_physical_cores: number | null;
  cpu_usage_percent: number | null;
  memory_used_percent: number | null;
  swap_used_percent: number | null;
  disk_used_percent: number | null;
  uptime_seconds: number | null;
  reboot_required: boolean | null;
}

export interface MetricsResponse {
  current: CurrentMetric | null;
  history: Metric[];
}

export interface MetricsFilter {
  period: string;
  from?: string;
  to?: string;
  [key: string]: number | string;
}
