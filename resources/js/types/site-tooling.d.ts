export type SiteToolingStatus = 'installing' | 'uninstalling' | 'install_failed' | 'uninstall_failed' | null;

export type SiteToolingProps = {
  isolated_user: string;
  sibling_sites: { id: number; domain: string; url: string }[];
  installed_versions: Record<string, string | null>;
  tool_statuses: Record<string, SiteToolingStatus>;
  required_tooling: Record<string, string>;
  watch_site_ids: number[];
};
