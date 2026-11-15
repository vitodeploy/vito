import { createInertiaApp } from '@inertiajs/react';

const appName = import.meta.env.VITE_APP_NAME || 'Vito';

createInertiaApp({
  pages: './pages',
  title: (title) => `${title} - ${appName}`,
  setup: ({ App, props }) => <App {...props} />,
});
