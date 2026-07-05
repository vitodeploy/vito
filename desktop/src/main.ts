import { app, BrowserWindow, dialog } from 'electron';
import { join } from 'node:path';
import { waitForHttp } from './health-check.js';
import { prepareLaravelRuntime, startLaravelProcesses } from './laravel-runtime.js';
import { loadingPage, errorPage } from './loading-page.js';
import { DesktopLog } from './logging.js';
import { ProcessSupervisor } from './process-supervisor.js';
import { createRuntimeContext } from './runtime-paths.js';

let mainWindow: BrowserWindow | null = null;
let supervisor: ProcessSupervisor | null = null;

const gotLock = app.requestSingleInstanceLock();

if (!gotLock) {
  app.quit();
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
  if (!supervisor) {
    return;
  }

  event.preventDefault();
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
    supervisor = new ProcessSupervisor(log);

    log.info(`Using Laravel app path: ${context.appRoot}`);
    log.info(`Using desktop data path: ${context.dataPath}`);

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
      sandbox: false,
    },
  });
}
