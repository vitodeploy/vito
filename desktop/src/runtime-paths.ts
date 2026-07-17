import { app } from 'electron';
import { existsSync, mkdirSync, writeFileSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { appKey, readEnvFile, secret, writeEnvFile } from './env-file.js';
import { getAvailablePort } from './ports.js';

export type RuntimeContext = {
  appRoot: string;
  dataPath: string;
  envPath: string;
  envFile: string;
  storagePath: string;
  runtimePath: string;
  bootstrapCachePath: string;
  logPath: string;
  httpPort: number;
  websocketPort: number;
  appUrl: string;
  phpBinary: string;
  phpArgs: string[];
  env: NodeJS.ProcessEnv;
  redactions: string[];
};

export async function createRuntimeContext(): Promise<RuntimeContext> {
  const dataPath = app.getPath('userData');
  const runtimePath = join(dataPath, 'runtime');
  const envPath = runtimePath;
  const envFile = join(envPath, '.env');
  const storagePath = join(dataPath, 'storage');
  const bootstrapCachePath = join(runtimePath, 'cache');
  const logPath = join(storagePath, 'logs', 'desktop-processes.log');
  const httpPort = await getAvailablePort();
  const websocketPort = await getAvailablePort();
  const appUrl = `http://127.0.0.1:${httpPort}`;
  const appRoot = resolveAppRoot();
  const phpBinary = resolvePhpBinary();
  const phpArgs = resolvePhpArgs(phpBinary);

  ensureRuntimeDirectories(storagePath, runtimePath, bootstrapCachePath);
  ensureFile(join(storagePath, 'database.sqlite'));

  const existing = readEnvFile(envFile);
  const envValues = {
    ...existing,
    APP_NAME: existing.APP_NAME || 'Vito',
    APP_ENV: 'production',
    APP_KEY: existing.APP_KEY || appKey(),
    APP_DEBUG: 'false',
    APP_URL: appUrl,
    VITO_DESKTOP: 'true',
    VITO_DATA_PATH: dataPath,
    VITO_ENV_PATH: envPath,
    VITO_STORAGE_PATH: storagePath,
    APP_SERVICES_CACHE: join(bootstrapCachePath, 'services.php'),
    APP_PACKAGES_CACHE: join(bootstrapCachePath, 'packages.php'),
    APP_CONFIG_CACHE: join(bootstrapCachePath, 'config.php'),
    APP_ROUTES_CACHE: join(bootstrapCachePath, 'routes.php'),
    APP_EVENTS_CACHE: join(bootstrapCachePath, 'events.php'),
    VIEW_COMPILED_PATH: join(storagePath, 'framework', 'views'),
    DB_CONNECTION: 'sqlite',
    DB_DATABASE: 'database.sqlite',
    QUEUE_CONNECTION: 'database',
    DB_QUEUE: 'default',
    QUEUE_FAILED_DRIVER: 'database-uuids',
    CACHE_DRIVER: 'file',
    SESSION_DRIVER: 'file',
    FILESYSTEM_DISK: 'local',
    MAIL_MAILER: existing.MAIL_MAILER || 'log',
    WS_HOST: '127.0.0.1',
    WS_PORT: String(websocketPort),
    WS_ALLOWED_ORIGINS: appUrl,
    WS_BROADCAST_SECRET: existing.WS_BROADCAST_SECRET || secret(),
  };

  writeEnvFile(envFile, envValues);

  const env: NodeJS.ProcessEnv = {
    ...process.env,
    ...envValues,
    VITO_ENV_PATH: envPath,
    VITO_STORAGE_PATH: storagePath,
  };

  const caBundle = join(dirname(phpBinary), 'cacert.pem');
  if (existsSync(caBundle)) {
    env.SSL_CERT_FILE = caBundle;
    env.CURL_CA_BUNDLE = caBundle;
  }

  return {
    appRoot,
    dataPath,
    envPath,
    envFile,
    storagePath,
    runtimePath,
    bootstrapCachePath,
    logPath,
    httpPort,
    websocketPort,
    appUrl,
    phpBinary,
    phpArgs,
    env,
    redactions: [envValues.APP_KEY, envValues.WS_BROADCAST_SECRET],
  };
}

function ensureRuntimeDirectories(storagePath: string, runtimePath: string, bootstrapCachePath: string): void {
  const directories = [
    runtimePath,
    bootstrapCachePath,
    join(storagePath, 'app'),
    join(storagePath, 'app', 'public'),
    join(storagePath, 'framework'),
    join(storagePath, 'framework', 'cache'),
    join(storagePath, 'framework', 'cache', 'data'),
    join(storagePath, 'framework', 'sessions'),
    join(storagePath, 'framework', 'views'),
    join(storagePath, 'logs'),
    join(storagePath, 'plugins'),
  ];

  for (const directory of directories) {
    mkdirSync(directory, { recursive: true, mode: 0o700 });
  }
}

function ensureFile(path: string): void {
  if (!existsSync(path)) {
    writeFileSync(path, '', { mode: 0o600 });
  }
}

function resolveAppRoot(): string {
  if (app.isPackaged) {
    return join(process.resourcesPath, 'app');
  }

  return resolve(app.getAppPath(), '..');
}

function resolvePhpBinary(): string {
  if (process.env.PHP_BINARY) {
    return process.env.PHP_BINARY;
  }

  if (!app.isPackaged) {
    return 'php';
  }

  const exe = process.platform === 'win32' ? 'php.exe' : 'php';
  return join(process.resourcesPath, 'bin', process.platform, process.arch, exe);
}

function resolvePhpArgs(phpBinary: string): string[] {
  const args: string[] = [];
  const binDir = dirname(phpBinary);

  if (app.isPackaged) {
    args.push('-d', 'memory_limit=512M');
  }

  const caBundle = join(binDir, 'cacert.pem');
  if (existsSync(caBundle)) {
    args.push('-d', `curl.cainfo=${caBundle}`, '-d', `openssl.cafile=${caBundle}`);
  }

  const extensionDir = join(binDir, 'ext');
  if (process.platform === 'win32' && existsSync(extensionDir)) {
    args.push('-d', `extension_dir=${extensionDir}`);
  }

  return args;
}
