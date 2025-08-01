import { useEffect, useState } from 'react';

export function usePageActive() {
  const getActive = () => typeof document !== 'undefined' && !document.hidden;

  const [active, setActive] = useState<boolean>(getActive());

  useEffect(() => {
    const update = () => setActive(getActive());
    document.addEventListener('visibilitychange', update);
    window.addEventListener('focus', update);
    return () => {
      document.removeEventListener('visibilitychange', update);
      window.removeEventListener('focus', update);
    };
  }, []);

  return active;
}
