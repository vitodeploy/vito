import { type ClassValue, clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs));
}

// convert kb to gb
export function kbToGb(kb: number | string): number {
  if (typeof kb === 'string') {
    kb = parseFloat(kb);
  }
  return Math.round((kb / 1024 / 1024) * 100) / 100;
}

// convert mb to gb
export function mbToGb(mb: number | string): number {
  if (typeof mb === 'string') {
    mb = parseFloat(mb);
  }
  return Math.round((mb / 1024) * 100) / 100;
}

export function formatDateString(dateString: string | Date): string {
  const date = new Date(dateString);

  const year = date.toLocaleString('default', { year: 'numeric' });
  const month = date.toLocaleString('default', { month: '2-digit' });
  const day = date.toLocaleString('default', { day: '2-digit' });

  // Generate yyyy-mm-dd date string
  return year + '-' + month + '-' + day;
}

export function humanizeSeconds(seconds: number | null | undefined): string {
  if (seconds == null || !Number.isFinite(seconds) || seconds < 0) return 'N/A';
  const total = Math.floor(seconds);
  const days = Math.floor(total / 86400);
  const hours = Math.floor((total % 86400) / 3600);
  const minutes = Math.floor((total % 3600) / 60);
  const parts: string[] = [];
  if (days) parts.push(`${days}d`);
  if (hours || days) parts.push(`${hours}h`);
  parts.push(`${minutes}m`);
  return parts.join(' ');
}

export function humanizeStep(step: string | null | undefined): string {
  if (!step) return '';
  const spaced = step.replace(/[-_]+/g, ' ').trim();
  return spaced.charAt(0).toUpperCase() + spaced.slice(1);
}
