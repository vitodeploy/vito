import { ChildProcessWithoutNullStreams, spawn } from 'node:child_process';
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
};

export class ProcessSupervisor {
  private readonly processes = new Map<string, ManagedProcess>();

  constructor(private readonly log: DesktopLog) {}

  start(options: ManagedProcessOptions): void {
    if (this.processes.has(options.name)) {
      return;
    }

    const process = new ManagedProcess(options, this.log);
    this.processes.set(options.name, process);
    process.start();
  }

  async stopAll(): Promise<void> {
    const processes = [...this.processes.values()].reverse();
    await Promise.all(processes.map((process) => process.stop()));
    this.processes.clear();
  }
}

class ManagedProcess {
  private child: ChildProcessWithoutNullStreams | null = null;
  private stopping = false;
  private restarts: number[] = [];

  constructor(
    private readonly options: ManagedProcessOptions,
    private readonly log: DesktopLog,
  ) {}

  start(): void {
    this.stopping = false;
    this.log.info(`Starting ${this.options.name}: ${this.options.command} ${this.options.args.join(' ')}`);

    this.child = spawn(this.options.command, this.options.args, {
      cwd: this.options.cwd,
      env: this.options.env,
      stdio: 'pipe',
      windowsHide: true,
    });

    this.child.stdout.on('data', (chunk: Buffer) => this.log.line(this.options.name, chunk.toString()));
    this.child.stderr.on('data', (chunk: Buffer) => this.log.line(`${this.options.name}:stderr`, chunk.toString()));

    this.child.on('error', (error) => {
      this.log.error(`${this.options.name} failed to start: ${error.message}`);
    });

    this.child.on('exit', (code, signal) => {
      this.log.info(`${this.options.name} exited with code=${code ?? 'null'} signal=${signal ?? 'null'}`);
      this.child = null;

      if (!this.stopping && this.options.persistent !== false && this.canRestart()) {
        setTimeout(() => this.start(), 1000);
      }
    });
  }

  async stop(timeoutMs = 8000): Promise<void> {
    this.stopping = true;

    if (!this.child || this.child.killed) {
      return;
    }

    const child = this.child;

    await new Promise<void>((resolve) => {
      const timeout = setTimeout(() => {
        if (!child.killed) {
          child.kill('SIGKILL');
        }
        resolve();
      }, timeoutMs);

      child.once('exit', () => {
        clearTimeout(timeout);
        resolve();
      });

      child.kill('SIGTERM');
    });
  }

  private canRestart(): boolean {
    const restartWindowMs = this.options.restartWindowMs ?? 60000;
    const maxRestarts = this.options.maxRestarts ?? Number.POSITIVE_INFINITY;
    const now = Date.now();

    this.restarts = this.restarts.filter((timestamp) => now - timestamp < restartWindowMs);

    if (this.restarts.length >= maxRestarts) {
      this.log.error(`${this.options.name} restart limit reached`);
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
