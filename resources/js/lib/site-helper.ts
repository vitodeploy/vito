import { Site } from '@/types/site';

export type StoredSite = Pick<Site, 'id' | 'server_id' | 'user' | 'status' | 'stats_enabled'>;

const STORAGE_KEY = 'selected-site';
const LEGACY_STORAGE_KEY = 'site';

const siteHelper = {
  getStoredSite() {
    const storedSite = localStorage.getItem(STORAGE_KEY);
    if (storedSite) {
      return JSON.parse(storedSite) as StoredSite;
    }
    return null;
  },
  storeSite(site?: Site) {
    localStorage.removeItem(LEGACY_STORAGE_KEY);
    if (!site) {
      localStorage.removeItem(STORAGE_KEY);
      return;
    }
    const { id, server_id, user, status, stats_enabled } = site;
    localStorage.setItem(STORAGE_KEY, JSON.stringify({ id, server_id, user, status, stats_enabled }));
  },
};

export default siteHelper;
