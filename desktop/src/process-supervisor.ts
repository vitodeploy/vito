import { ChildProcessWithoutNullStreams, execFileSync, spawn } from 'node:child_process';
import { existsSync, readFileSync, rmSync, writeFileSync } from 'node:fs';
import { basename } from 'node:path';
import { DesktopLog } from './logging.js';

export type ManagedProcessOptions = {
  name: string;
  command: string;
  args: string[];
  cwd: string;
  env: NodeJS.ProcessEnv;
  persistent?: boolean;
  maxRestarts?: number;
  restartWindowMs?: number;
  restartBackoffMaxMs?: number;
  gracefulStop?: {
    command: string;
    args: string[];
    timeoutMs?: number;
  };
};

export class ProcessSupervisor {
  private readonly processes = new Map<string, ManagedProcess>();

  constructor(
    private readonly log: DesktopLog,
    private readonly pidFilePath: string,
    private readonly onRestartLimitReached?: (name: string) => void,
  ) {}

  start(options: ManagedProcessOptions): void {
    if (this.processes.has(options.name)) {
      return;
    }

    const process = new ManagedProcess(options, this.log, this.onRestartLimitReached, () => this.recordPids());
    this.processes.set(options.name, process);
    process.start();
  }

  async stopAll(): Promise<void> {
    const shutdownOrder = ['vito-queue', 'vito-scheduler', 'vito-websocket', 'vito-http'];
    const ordered = shutdownOrder
      .map((name) => this.processes.get(name))
      .filter((process): process is ManagedProcess => process !== undefined);
    const remaining = [...this.processes.entries()]
      .filter(([name]) => !shutdownOrder.includes(name))
      .map(([, process]) => process);

    for (const process of [...ordered, ...remaining]) {
      await process.stop();
    }
    this.processes.clear();
    rmSync(this.pidFilePath, { force: true });
  }

  private recordPids(): void {
    const entries = [...this.processes.values()]
      .map((process) => process.describe())
      .filter((entry): entry is StaleProcessEntry => entry !== null);

    try {
      writeFileSync(this.pidFilePath, JSON.stringify(entries), 'utf8');
    } catch (error) {
      const message = error instanceof Error ? error.message : String(error);
      this.log.error(`Unable to record supervised process pids: ${message}`);
    }
  }
}

type StaleProcessEntry = {
  pid: number;
  name: string;
  command: string;
};

export function cleanupStaleProcesses(pidFilePath: string, log: DesktopLog): void {
  if (!existsSync(pidFilePath)) {
    return;
  }

  try {
    const entries = JSON.parse(readFileSync(pidFilePath, 'utf8')) as StaleProcessEntry[];

    for (const entry of entries) {
      if (typeof entry?.pid !== 'number' || typeof entry?.command !== 'string') {
        continue;
      }

      for (const pid of findStalePids(entry)) {
        log.info(`Killing stale ${entry.name} process ${pid} left over from a previous session`);

        try {
          process.kill(pid, 'SIGKILL');
        } catch {
          continue;
        }
      }
    }
  } catch (error) {
    const message = error instanceof Error ? error.message : String(error);
    log.error(`Unable to clean up stale processes: ${message}`);
  }

  rmSync(pidFilePath, { force: true });
}

function findStalePids(entry: StaleProcessEntry): number[] {
  try {
    if (process.platform === 'win32') {
      const output = execFileSync('tasklist', ['/FI', `PID eq ${entry.pid}`, '/FO', 'CSV', '/NH'], { encoding: 'utf8' });

      return output.toLowerCase().includes(basename(entry.command.split(' ')[0]).toLowerCase()) ? [entry.pid] : [];
    }

    const output = execFileSync('ps', ['-axo', 'pid=,pgid=,command='], { encoding: 'utf8', maxBuffer: 16 * 1024 * 1024 });
    const pids: number[] = [];

    for (const line of output.split('\n')) {
      const match = line.match(/^\s*(\d+)\s+(\d+)\s+(.*)$/);
      if (!match) {
        continue;
      }

      const pid = Number(match[1]);
      const pgid = Number(match[2]);

      if ((pid === entry.pid || pgid === entry.pid) && match[3].includes(entry.command)) {
        pids.push(pid);
      }
    }

    return pids;
  } catch {
    return [];
  }
}

function killProcessTree(child: ChildProcessWithoutNullStreams, signal: NodeJS.Signals): void {
  if (process.platform !== 'win32' && typeof child.pid === 'number' && killProcessGroup(child.pid, signal)) {
    return;
  }

  child.kill(signal);
}

function killProcessGroup(pid: number, signal: NodeJS.Signals): boolean {
  try {
    process.kill(-pid, signal);

    return true;
  } catch {
    return false;
  }
}

class ManagedProcess {
  private child: ChildProcessWithoutNullStreams | null = null;
  private stopping = false;
  private restarts: number[] = [];
  private consecutiveRestarts = 0;
  private startedAt = 0;
  private restartTimer: NodeJS.Timeout | null = null;

  constructor(
    private readonly options: ManagedProcessOptions,
    private readonly log: DesktopLog,
    private readonly onRestartLimitReached?: (name: string) => void,
    private readonly onChange?: () => void,
  ) {}

  describe(): { pid: number; name: string; command: string } | null {
    if (!this.child || typeof this.child.pid !== 'number') {
      return null;
    }

    return {
      pid: this.child.pid,
      name: this.options.name,
      command: [this.options.command, ...this.options.args].join(' '),
    };
  }

  start(): void {
    this.stopping = false;
    this.startedAt = Date.now();
    this.log.info(`Starting ${this.options.name}: ${this.options.command} ${this.options.args.join(' ')}`);

    this.child = spawn(this.options.command, this.options.args, {
      cwd: this.options.cwd,
      env: this.options.env,
      stdio: 'pipe',
      windowsHide: true,
      detached: process.platform !== 'win32',
    });

    this.onChange?.();

    this.child.stdout.on('data', (chunk: Buffer) => this.log.line(this.options.name, chunk.toString()));
    this.child.stderr.on('data', (chunk: Buffer) => this.log.line(`${this.options.name}:stderr`, chunk.toString()));

    this.child.on('error', (error) => {
      this.log.error(`${this.options.name} failed to start: ${error.message}`);
    });

    this.child.on('exit', (code, signal) => {
      this.log.info(`${this.options.name} exited with code=${code ?? 'null'} signal=${signal ?? 'null'}`);
      this.child = null;
      this.onChange?.();

      if (Date.now() - this.startedAt >= 60000) {
        this.consecutiveRestarts = 0;
      }

      if (!this.stopping && this.options.persistent !== false && this.canRestart()) {
        const delay = Math.min(1000 * 2 ** this.consecutiveRestarts, this.options.restartBackoffMaxMs ?? 30000);
        this.consecutiveRestarts += 1;
        this.log.info(`Restarting ${this.options.name} in ${delay}ms`);
        this.restartTimer = setTimeout(() => {
          this.restartTimer = null;
          this.start();
        }, delay);
      }
    });
  }

  async stop(timeoutMs = 8000): Promise<void> {
    this.stopping = true;

    if (this.restartTimer) {
      clearTimeout(this.restartTimer);
      this.restartTimer = null;
    }

    if (!this.child || this.child.exitCode !== null || this.child.signalCode !== null) {
      return;
    }

    const child = this.child;

    if (this.options.gracefulStop) {
      try {
        await runCommand(this.options.gracefulStop.command, this.options.gracefulStop.args, {
          cwd: this.options.cwd,
          env: this.options.env,
          log: this.log,
          name: `${this.options.name}:graceful-stop`,
          timeoutMs: this.options.gracefulStop.timeoutMs ?? 30000,
        });
        await this.waitForExit(child, timeoutMs);
        return;
      } catch (error) {
        const message = error instanceof Error ? error.message : String(error);
        this.log.error(`${this.options.name} graceful stop failed: ${message}`);
      }
    }

    await this.terminate(child, timeoutMs);
  }

  private waitForExit(child: ChildProcessWithoutNullStreams, timeoutMs: number): Promise<void> {
    return new Promise((resolve, reject) => {
      if (child.exitCode !== null || child.signalCode !== null) {
        resolve();
        return;
      }

      const timeout = setTimeout(() => {
        reject(new Error(`${this.options.name} did not exit gracefully`));
      }, timeoutMs);

      child.once('exit', () => {
        clearTimeout(timeout);
        resolve();
      });
    });
  }

  private async terminate(child: ChildProcessWithoutNullStreams, timeoutMs: number): Promise<void> {
    await new Promise<void>((resolve) => {
      const timeout = setTimeout(() => {
        if (child.exitCode === null && child.signalCode === null) {
          killProcessTree(child, 'SIGKILL');
        }
        resolve();
      }, timeoutMs);

      child.once('exit', () => {
        clearTimeout(timeout);
        resolve();
      });

      killProcessTree(child, 'SIGTERM');
    });
  }

  private canRestart(): boolean {
    const restartWindowMs = this.options.restartWindowMs ?? 60000;
    const maxRestarts = this.options.maxRestarts ?? Number.POSITIVE_INFINITY;
    const now = Date.now();

    this.restarts = this.restarts.filter((timestamp) => now - timestamp < restartWindowMs);

    if (this.restarts.length >= maxRestarts) {
      this.log.error(`${this.options.name} restart limit reached`);
      this.onRestartLimitReached?.(this.options.name);
      return false;
    }

    this.restarts.push(now);
    return true;
  }
}

export function runCommand(
  command: string,
  args: string[],
  options: { cwd: string; env: NodeJS.ProcessEnv; log: DesktopLog; name: string; timeoutMs?: number },
): Promise<void> {
  options.log.info(`Running ${options.name}: ${command} ${args.join(' ')}`);

  return new Promise((resolve, reject) => {
    const child = spawn(command, args, {
      cwd: options.cwd,
      env: options.env,
      stdio: 'pipe',
      windowsHide: true,
    });

    const timeout = options.timeoutMs
      ? setTimeout(() => {
          child.kill('SIGKILL');
          reject(new Error(`${options.name} timed out`));
        }, options.timeoutMs)
      : null;

    child.stdout.on('data', (chunk: Buffer) => options.log.line(options.name, chunk.toString()));
    child.stderr.on('data', (chunk: Buffer) => options.log.line(`${options.name}:stderr`, chunk.toString()));
    child.on('error', reject);
    child.on('exit', (code) => {
      if (timeout) {
        clearTimeout(timeout);
      }

      if (code === 0) {
        resolve();
      } else {
        reject(new Error(`${options.name} exited with code ${code}`));
      }
    });
  });
}
