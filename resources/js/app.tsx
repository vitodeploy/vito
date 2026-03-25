import '../css/app.css';

import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { initializeTheme } from './hooks/use-appearance';
import './vito-table-setup';

const appName = import.meta.env.VITE_APP_NAME || 'Vito';

createInertiaApp({
  title: (title) => `${title} - ${appName}`,
  resolve: (name) => resolvePageComponent(`./pages/${name}.tsx`, import.meta.glob('./pages/**/*.tsx')),
  setup({ el, App, props }) {
    const root = createRoot(el);

    root.render(<App {...props} />);
  },
  progress: {
    color: '#5a5bc5',
  },
});

// This will set light / dark mode on load...
initializeTheme();
