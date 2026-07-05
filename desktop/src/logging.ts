import { appendFileSync, mkdirSync } from 'node:fs';
import { dirname } from 'node:path';

export class DesktopLog {
  constructor(private readonly path: string) {
    mkdirSync(dirname(path), { recursive: true });
  }

  info(message: string): void {
    this.write('info', message);
  }

  error(message: string): void {
    this.write('error', message);
  }

  line(source: string, message: string): void {
    appendFileSync(this.path, `[${new Date().toISOString()}] [${source}] ${message}`, 'utf8');
  }

  private write(level: 'info' | 'error', message: string): void {
    appendFileSync(this.path, `[${new Date().toISOString()}] [desktop:${level}] ${message}\n`, 'utf8');
  }
}
