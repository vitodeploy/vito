import { TriangleAlertIcon } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

export default function ErrorIndicator({ error }: { error: string | null }) {
  if (!error) {
    return null;
  }

  return (
    <TooltipProvider delayDuration={0}>
      <Tooltip>
        <TooltipTrigger asChild>
          <button
            type="button"
            aria-label="Error"
            className="bg-destructive/15 text-destructive border-destructive/40 flex cursor-help items-center rounded-md border px-1.5 py-1"
          >
            <TriangleAlertIcon className="h-4 w-4" />
          </button>
        </TooltipTrigger>
        <TooltipContent className="max-w-sm">
          <pre className="font-mono text-xs whitespace-pre-wrap">{error}</pre>
        </TooltipContent>
      </Tooltip>
    </TooltipProvider>
  );
}
