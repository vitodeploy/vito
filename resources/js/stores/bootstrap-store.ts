import { create } from 'zustand';
import { Configs } from '@/types';

const STORAGE_KEY = 'vito.bootstrap';

type BootstrapStatus = 'idle' | 'loading' | 'ready' | 'error';

type BootstrapState = {
  version: string | null;
  configs: Configs | null;
  publicKeyText: string;
  status: BootstrapStatus;
  hydrateFromCache: () => void;
  fetch: () => Promise<void>;
  syncWithServerVersion: (serverVersion: string) => Promise<void>;
  clear: () => void;
};

type CachedBootstrap = {
  version: string;
  configs: Configs;
  public_key_text: string;
};

function readCache(): CachedBootstrap | null {
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw) return null;
    const parsed = JSON.parse(raw) as CachedBootstrap;
    if (!parsed.version || !parsed.configs) return null;
    return parsed;
  } catch {
    return null;
  }
}

function writeCache(payload: CachedBootstrap): void {
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
  } catch {
    // localStorage quota or disabled — ignore, store still works in-memory
  }
}

function clearCache(): void {
  try {
    localStorage.removeItem(STORAGE_KEY);
  } catch {
    // localStorage disabled — nothing to do
  }
}

export const useBootstrapStore = create<BootstrapState>((set, get) => {
  let inflight: Promise<void> | null = null;

  return {
    version: null,
    configs: null,
    publicKeyText: '',
    status: 'idle',

    hydrateFromCache: () => {
      const cached = readCache();
      if (cached) {
        set({
          version: cached.version,
          configs: cached.configs,
          publicKeyText: cached.public_key_text,
          status: 'ready',
        });
      }
    },

    fetch: async () => {
      if (inflight) return inflight;
      set({ status: get().status === 'ready' ? 'ready' : 'loading' });
      inflight = (async () => {
        try {
          const response = await fetch(route('bootstrap.show'), {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
          });
          if (!response.ok) throw new Error(`bootstrap fetch failed: ${response.status}`);
          const payload = (await response.json()) as CachedBootstrap;
          writeCache(payload);
          set({
            version: payload.version,
            configs: payload.configs,
            publicKeyText: payload.public_key_text,
            status: 'ready',
          });
        } catch {
          if (get().status !== 'ready') set({ status: 'error' });
        } finally {
          inflight = null;
        }
      })();
      return inflight;
    },

    syncWithServerVersion: async (serverVersion: string) => {
      const { version, configs, status } = get();
      if (status !== 'error' && configs !== null && version === serverVersion) return;
      await get().fetch();
    },

    clear: () => {
      clearCache();
      set({ version: null, configs: null, publicKeyText: '', status: 'idle' });
    },
  };
});

/**
 * Returns the configs catalogue. Layout gates render on `configs !== null`,
 * so descendants under the app layout can safely treat this as non-null.
 * Returns `null` outside the layout (e.g. on auth pages).
 */
export function useConfigs(): Configs | null {
  return useBootstrapStore((s) => s.configs);
}

export function usePublicKeyText(): string {
  return useBootstrapStore((s) => s.publicKeyText);
}
