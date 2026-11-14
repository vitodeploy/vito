import { createInertiaApp } from '@inertiajs/react';

// The @inertiajs/vite plugin wraps this `createInertiaApp(...)` call at build
// time, injecting `createServer(renderPage)` from `@inertiajs/react/server` so
// `php artisan inertia:start-ssr` can boot this bundle. Do not add createServer
// here by hand — see node_modules/@inertiajs/vite/dist/index.js.
const appName = import.meta.env.VITE_APP_NAME || 'Vito';

createInertiaApp({
  pages: './pages',
  title: (title) => `${title} - ${appName}`,
  setup: ({ App, props }) => <App {...props} />,
});
