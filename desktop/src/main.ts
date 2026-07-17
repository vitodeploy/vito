import { app, BrowserWindow, dialog, shell } from 'electron';
import { join } from 'node:path';
import { waitForHttp } from './health-check.js';
import { prepareLaravelRuntime, startLaravelProcesses } from './laravel-runtime.js';
import { loadingPage, errorPage } from './loading-page.js';
import { DesktopLog } from './logging.js';
import { cleanupStaleProcesses, ProcessSupervisor } from './process-supervisor.js';
import { createRuntimeContext } from './runtime-paths.js';

let mainWindow: BrowserWindow | null = null;
let supervisor: ProcessSupervisor | null = null;
let quitting = false;

const gotLock = app.requestSingleInstanceLock();

if (!gotLock) {
  app.quit();
}

for (const signal of ['SIGINT', 'SIGTERM'] as const) {
  process.on(signal, () => {
    app.quit();
  });
}

app.on('second-instance', () => {
  if (!mainWindow) {
    return;
  }

  if (mainWindow.isMinimized()) {
    mainWindow.restore();
  }

  mainWindow.focus();
});

app.on('before-quit', async (event) => {
  if (quitting) {
    event.preventDefault();

    return;
  }

  if (!supervisor) {
    return;
  }

  event.preventDefault();
  quitting = true;
  const currentSupervisor = supervisor;
  supervisor = null;
  await currentSupervisor.stopAll();
  app.exit(0);
});

app.whenReady().then(async () => {
  mainWindow = createWindow();
  await mainWindow.loadURL(loadingPage('Preparing local runtime...'));

  try {
    const context = await createRuntimeContext();
    const log = new DesktopLog(context.logPath);
    const pidFilePath = join(context.runtimePath, 'processes.json');

    cleanupStaleProcesses(pidFilePath, log);

    supervisor = new ProcessSupervisor(log, pidFilePath, (name) => {
      if (quitting || name !== 'vito-http') {
        return;
      }

      const message = 'The local Vito backend repeatedly crashed and could not be restarted.';
      log.error(message);
      void mainWindow?.loadURL(errorPage(message));
      dialog.showErrorBox('Vito backend stopped', message);
    });

    log.info(`Using Laravel app path: ${context.appRoot}`);
    log.info(`Using desktop data path: ${context.dataPath}`);

    attachNavigationGuards(mainWindow, context.appUrl);

    await mainWindow.loadURL(loadingPage('Migrating local database...'));
    await prepareLaravelRuntime(context, log);

    await mainWindow.loadURL(loadingPage('Starting backend services...'));
    startLaravelProcesses(context, supervisor);
    await waitForHttp(`${context.appUrl}/api/health`, 45000);

    await mainWindow.loadURL(`${context.appUrl}/desktop/login`);
  } catch (error) {
    const message = error instanceof Error ? error.message : String(error);
    await mainWindow.loadURL(errorPage(message));
    dialog.showErrorBox('Vito could not start', message);
  }
});

app.on('window-all-closed', () => {
  app.quit();
});

function createWindow(): BrowserWindow {
  return new BrowserWindow({
    width: 1440,
    height: 960,
    minWidth: 1024,
    minHeight: 700,
    title: 'Vito',
    webPreferences: {
      contextIsolation: true,
      nodeIntegration: false,
      preload: join(app.getAppPath(), 'dist', 'preload.js'),
      sandbox: true,
    },
  });
}

function attachNavigationGuards(window: BrowserWindow, appUrl: string): void {
  const openExternally = (url: string): void => {
    if (url.startsWith('http://') || url.startsWith('https://')) {
      void shell.openExternal(url);
    }
  };

  window.webContents.setWindowOpenHandler(({ url }) => {
    openExternally(url);

    return { action: 'deny' };
  });

  window.webContents.on('will-navigate', (event, url) => {
    if (url === appUrl || url.startsWith(`${appUrl}/`)) {
      return;
    }

    event.preventDefault();
    openExternally(url);
  });
}
