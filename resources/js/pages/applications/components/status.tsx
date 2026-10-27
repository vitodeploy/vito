import { cn, humanizeStep } from '@/lib/utils';
import { variants } from '@/components/status-ripple';

const colors = ['default', 'success', 'info', 'warning', 'danger', 'destructive', 'gray'] as const;

export default function Status({ status, color, label, className }: { status: string; color: string; label?: string; className?: string }) {
  const variant = colors.find((item) => item === color) ?? 'gray';
  const busy = variant === 'warning' || variant === 'info';

  return (
    <span className={cn('inline-flex items-center gap-1.5 text-sm', className)}>
      <span className="relative flex size-2 shrink-0" aria-hidden="true">
        {busy && <span className={cn('absolute inline-flex size-full animate-ping rounded-full opacity-75', variants({ variant }))} />}
        <span className={cn('relative inline-flex size-2 rounded-full', variants({ variant }))} />
      </span>
      {label ?? humanizeStep(status)}
      {label !== undefined && status && <span className="sr-only">({humanizeStep(status)})</span>}
    </span>
  );
}
