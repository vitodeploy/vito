import { randomBytes } from 'node:crypto';
import { chmodSync, existsSync, readFileSync, writeFileSync } from 'node:fs';

const SIMPLE_VALUE = /^[A-Za-z0-9_./:@-]*$/;

export function readEnvFile(path: string): Record<string, string> {
  if (!existsSync(path)) {
    return {};
  }

  const values: Record<string, string> = {};
  const lines = readFileSync(path, 'utf8').split(/\r?\n/);

  for (const line of lines) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith('#')) {
      continue;
    }

    const equalsAt = trimmed.indexOf('=');
    if (equalsAt === -1) {
      continue;
    }

    const key = trimmed.slice(0, equalsAt).trim();
    let value = trimmed.slice(equalsAt + 1).trim();

    if (value.startsWith('"') && value.endsWith('"')) {
      value = value.slice(1, -1).replace(/\\(["\\])/g, '$1');
    } else if (value.startsWith("'") && value.endsWith("'")) {
      value = value.slice(1, -1);
    }

    values[key] = value;
  }

  return values;
}

export function writeEnvFile(path: string, values: Record<string, string>): void {
  const lines = Object.entries(values).map(([key, value]) => `${key}=${formatEnvValue(value)}`);
  writeFileSync(path, `${lines.join('\n')}\n`, { encoding: 'utf8', mode: 0o600 });

  try {
    chmodSync(path, 0o600);
  } catch {
    return;
  }
}

export function appKey(): string {
  return `base64:${randomBytes(32).toString('base64')}`;
}

export function secret(): string {
  return randomBytes(32).toString('hex');
}

function formatEnvValue(value: string): string {
  if (value === '') {
    return '';
  }

  if (SIMPLE_VALUE.test(value)) {
    return value;
  }

  return `"${value.replace(/\\/g, '\\\\').replace(/"/g, '\\"')}"`;
}
