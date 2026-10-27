import { useCallback, useEffect, useState } from 'react';

export type Appearance = 'light' | 'dark' | 'system';

const APPEARANCE_EVENT = 'vito:appearance-change';

const prefersDark = () => {
  if (typeof window === 'undefined') {
    return false;
  }

  return window.matchMedia('(prefers-color-scheme: dark)').matches;
};

const setCookie = (name: string, value: string, days = 365) => {
  if (typeof document === 'undefined') {
    return;
  }

  const maxAge = days * 24 * 60 * 60;
  document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};

const applyTheme = (appearance: Appearance) => {
  const isDark = appearance === 'dark' || (appearance === 'system' && prefersDark());

  document.documentElement.classList.toggle('dark', isDark);
};

const mediaQuery = () => {
  if (typeof window === 'undefined') {
    return null;
  }

  return window.matchMedia('(prefers-color-scheme: dark)');
};

const handleSystemThemeChange = () => {
  const currentAppearance = localStorage.getItem('appearance') as Appearance;
  applyTheme(currentAppearance || 'system');
};

export function initializeTheme() {
  const savedAppearance = (localStorage.getItem('appearance') as Appearance) || 'system';

  applyTheme(savedAppearance);

  // Add the event listener for system theme changes...
  mediaQuery()?.addEventListener('change', handleSystemThemeChange);
}

export function useAppearance() {
  const [appearance, setAppearance] = useState<Appearance>('system');
  const [systemAppearance, setSystemAppearance] = useState<'light' | 'dark'>('light');

  const updateAppearance = useCallback((mode: Appearance) => {
    // Store in localStorage for client-side persistence...
    localStorage.setItem('appearance', mode);

    // Store in cookie for SSR...
    setCookie('appearance', mode);

    applyTheme(mode);
    window.dispatchEvent(new Event(APPEARANCE_EVENT));
  }, []);

  // create a method to return the actual appearance light or dark
  const getActualAppearance = useCallback(() => {
    if (appearance === 'system') {
      return systemAppearance;
    }
    return appearance;
  }, [appearance, systemAppearance]);

  useEffect(() => {
    const syncAppearance = () => {
      const savedAppearance = (localStorage.getItem('appearance') as Appearance) || 'system';
      setAppearance(savedAppearance);
      setSystemAppearance(prefersDark() ? 'dark' : 'light');
      applyTheme(savedAppearance);
    };
    const query = mediaQuery();
    syncAppearance();
    window.addEventListener(APPEARANCE_EVENT, syncAppearance);
    window.addEventListener('storage', syncAppearance);
    query?.addEventListener('change', syncAppearance);

    return () => {
      window.removeEventListener(APPEARANCE_EVENT, syncAppearance);
      window.removeEventListener('storage', syncAppearance);
      query?.removeEventListener('change', syncAppearance);
    };
  }, []);

  return { appearance, updateAppearance, getActualAppearance } as const;
}
