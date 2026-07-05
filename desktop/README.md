# Vito Desktop

This is the initial desktop shell for Vito. It runs the Laravel app as a local backend process, supervises the queue, scheduler, and websocket workers, then opens the existing Inertia UI in Electron.

Development:

```sh
npm install --prefix desktop
npm run desktop:dev
```

The dev shell uses the repository root as the Laravel app path and the system PHP binary unless `PHP_BINARY` is set. Packaged builds are expected to place a prepared Laravel app in `desktop-build/app` and platform PHP binaries in `desktop/resources/bin`.
