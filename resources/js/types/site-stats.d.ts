export interface SiteStatsMonthSummary {
  month: string;
  visitors: number;
  hits: number;
  bandwidth: number;
  [key: string]: string | number;
}

export interface SiteStatsDaily {
  date: string;
  visitors: number;
  hits: number;
  bandwidth: number;
  [key: string]: string | number;
}

export interface SiteStatsPanelRow {
  name: string;
  hits: number;
  visitors: number;
  bandwidth: number;
}

export interface SiteStatsStatusCode {
  name: string;
  hits: number;
}

export interface SiteStatsDetail {
  generated_at: string | null;
  totals: {
    visitors: number;
    hits: number;
    bandwidth: number;
  };
  daily: SiteStatsDaily[];
  top_pages: SiteStatsPanelRow[];
  referrers: SiteStatsPanelRow[];
  status_codes: SiteStatsStatusCode[];
  not_found: SiteStatsPanelRow[];
}

export interface SiteStatsStatus {
  last_success_at: string | null;
  last_run_finished_at: string | null;
  exit_code: number | null;
  error: string | null;
}

export interface SiteStatsResponse {
  months: string[];
  month: string;
  summary: SiteStatsMonthSummary[];
  detail: SiteStatsDetail | null;
  status: SiteStatsStatus | null;
}
