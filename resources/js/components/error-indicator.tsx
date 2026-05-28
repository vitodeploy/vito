import { TriangleAlertIcon } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';

export default function ErrorIndicator({ error, label = 'View error' }: { error: string | null; label?: string }) {
  if (!error) {
    return null;
  }

  return (
    <Tooltip delayDuration={0}>
      <TooltipTrigger asChild>
        <button
          type="button"
          aria-label={label}
          className="bg-destructive/10 text-destructive border-destructive/40 flex cursor-help items-center rounded-md border px-1.5 py-1"
        >
          <TriangleAlertIcon className="h-4 w-4" />
        </button>
      </TooltipTrigger>
      <TooltipContent className="max-w-sm">
        <pre className="font-mono text-xs whitespace-pre-wrap">{error}</pre>
      </TooltipContent>
    </Tooltip>
  );
}
