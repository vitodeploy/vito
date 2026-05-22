import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';
import { useBootstrapStore } from './stores/bootstrap-store';
import './vito-table-setup';

const appName = import.meta.env.VITE_APP_NAME || 'Vito';

useBootstrapStore.getState().hydrateFromCache();

createInertiaApp({
  pages: './pages',
  title: (title) => `${title} - ${appName}`,
  setup({ el, App, props }) {
    const root = createRoot(el!);

    root.render(<App {...props} />);
  },
  progress: {
    color: '#5a5bc5',
  },
});

initializeTheme();
