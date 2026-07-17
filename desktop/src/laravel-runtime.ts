import { existsSync } from 'node:fs';
import { join } from 'node:path';
import { DesktopLog } from './logging.js';
import { ProcessSupervisor, runCommand } from './process-supervisor.js';
import { RuntimeContext } from './runtime-paths.js';

export async function prepareLaravelRuntime(context: RuntimeContext, log: DesktopLog): Promise<void> {
  await artisan(context, log, ['optimize:clear'], 'artisan:optimize-clear', 120000);
  await artisan(context, log, ['migrate', '--force'], 'artisan:migrate', 120000);
  await artisan(context, log, ['optimize'], 'artisan:optimize', 120000);
  await ensureSshKeys(context, log);
}

export function startLaravelProcesses(context: RuntimeContext, supervisor: ProcessSupervisor): void {
  const common = {
    cwd: context.appRoot,
    env: context.env,
  };

  supervisor.start({
    name: 'vito-websocket',
    command: context.phpBinary,
    args: [...context.phpArgs, 'artisan', 'ws:serve', '--host=127.0.0.1', `--port=${context.websocketPort}`],
    ...common,
  });

  supervisor.start({
    name: 'vito-queue',
    command: context.phpBinary,
    args: [
      ...context.phpArgs,
      'artisan',
      'queue:work',
      'database',
      '--queue=default,ssh,ssh-certbot',
      '--sleep=3',
      '--timeout=3600',
      '--tries=1',
    ],
    gracefulStop: {
      command: context.phpBinary,
      args: [...context.phpArgs, 'artisan', 'queue:restart'],
    },
    ...common,
  });

  supervisor.start({
    name: 'vito-scheduler',
    command: context.phpBinary,
    args: [...context.phpArgs, 'artisan', 'schedule:work'],
    ...common,
  });

  supervisor.start({
    name: 'vito-http',
    command: context.phpBinary,
    args: [
      ...context.phpArgs,
      '-S',
      `127.0.0.1:${context.httpPort}`,
      join(context.appRoot, 'vendor', 'laravel', 'framework', 'src', 'Illuminate', 'Foundation', 'resources', 'server.php'),
    ],
    maxRestarts: 3,
    restartWindowMs: 60000,
    cwd: join(context.appRoot, 'public'),
    env: process.platform === 'win32' ? context.env : { ...context.env, PHP_CLI_SERVER_WORKERS: '8' },
  });
}

function artisan(
  context: RuntimeContext,
  log: DesktopLog,
  args: string[],
  name: string,
  timeoutMs?: number,
): Promise<void> {
  return runCommand(context.phpBinary, [...context.phpArgs, 'artisan', ...args], {
    cwd: context.appRoot,
    env: context.env,
    log,
    name,
    timeoutMs,
  });
}

async function ensureSshKeys(context: RuntimeContext, log: DesktopLog): Promise<void> {
  const publicKey = join(context.storagePath, 'ssh-public.key');
  const privateKey = join(context.storagePath, 'ssh-private.pem');

  if (existsSync(publicKey) && existsSync(privateKey)) {
    return;
  }

  await artisan(context, log, ['ssh-key:generate'], 'artisan:ssh-key-generate', 120000);

  if (!existsSync(publicKey) || !existsSync(privateKey)) {
    throw new Error('SSH key generation completed without creating both required key files');
  }
}
