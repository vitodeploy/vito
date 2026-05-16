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

export function humanizeStep(step: string | null | undefined): string {
  if (!step) return '';
  const spaced = step.replace(/[-_]+/g, ' ').trim();
  return spaced.charAt(0).toUpperCase() + spaced.slice(1);
}
