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

// Copy text to clipboard with fallback to text selection for localhost
export async function copyToClipboard(text: string, inputElement?: HTMLInputElement): Promise<boolean> {
  try {
    // Try clipboard API first
    await navigator.clipboard.writeText(text);
    return true;
  } catch {
    // Fallback: if input element is provided, select the text so user can copy with Cmd+C
    if (inputElement) {
      inputElement.focus();
      inputElement.select();
      // Try the legacy execCommand as a last resort
      try {
        return document.execCommand('copy');
      } catch {
        // If all fails, at least the text is selected
        return false;
      }
    }
    return false;
  }
}
