import { appendFileSync, mkdirSync } from 'node:fs';
import { dirname } from 'node:path';

export class DesktopLog {
  constructor(
    private readonly path: string,
    private readonly redactions: string[] = [],
  ) {
    mkdirSync(dirname(path), { recursive: true });
  }

  info(message: string): void {
    this.write('info', message);
  }

  error(message: string): void {
    this.write('error', message);
  }

  line(source: string, message: string): void {
    const redacted = this.redact(message);
    const terminated = redacted.endsWith('\n') ? redacted : `${redacted}\n`;
    appendFileSync(this.path, `[${new Date().toISOString()}] [${source}] ${terminated}`, 'utf8');
  }

  private write(level: 'info' | 'error', message: string): void {
    appendFileSync(this.path, `[${new Date().toISOString()}] [desktop:${level}] ${this.redact(message)}\n`, 'utf8');
  }

  private redact(message: string): string {
    let redacted = message;

    for (const secret of this.redactions) {
      if (secret !== '') {
        redacted = redacted.split(secret).join('[redacted]');
      }
    }

    return redacted;
  }
}
