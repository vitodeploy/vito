import { createInertiaApp } from '@inertiajs/react';
import { route } from 'ziggy-js';

// The @inertiajs/vite plugin wraps this `createInertiaApp(...)` call at build
// time, injecting `createServer(renderPage)` from `@inertiajs/react/server` so
// `php artisan inertia:start-ssr` can boot this bundle. Do not add createServer
// here by hand — see node_modules/@inertiajs/vite/dist/index.js.
const appName = import.meta.env.VITE_APP_NAME || 'Vito';

createInertiaApp({
  pages: './pages',
  title: (title) => `${title} - ${appName}`,
  setup: ({ App, props }) => {
    const ziggy = (
      props.initialPage.props as unknown as {
        ziggy?: { location: string } & Record<string, unknown>;
      }
    ).ziggy;

    if (!ziggy) {
      return <App {...props} />;
    }

    /* eslint-disable @typescript-eslint/no-explicit-any */
    (globalThis as any).route = (name?: unknown, params?: unknown, absolute?: boolean) =>
      route(name as any, params as any, absolute, {
        ...ziggy,
        location: new URL(ziggy.location),
      } as any);
    /* eslint-enable @typescript-eslint/no-explicit-any */

    return <App {...props} />;
  },
});
