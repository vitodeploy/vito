import { useCallback } from 'react';

export function useMobileNavigation() {
  return useCallback(() => {
    // Remove pointer-events style from body...
    document.body.style.removeProperty('pointer-events');
  }, []);
}
