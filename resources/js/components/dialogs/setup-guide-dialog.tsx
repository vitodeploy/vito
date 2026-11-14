import { useEffect, useRef, useState } from 'react';
import { CheckIcon, CopyIcon } from 'lucide-react';
import { toast } from 'sonner';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { SetupGuideStep } from '@/types/dynamic-field-config';

export default function SetupGuideDialog({
  open,
  onOpenChange,
  title,
  description,
  steps,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  title: string;
  description?: string;
  steps: SetupGuideStep[];
}) {
  const [copied, setCopied] = useState<number | null>(null);
  const resetTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  useEffect(
    () => () => {
      if (resetTimer.current) {
        clearTimeout(resetTimer.current);
      }
    },
    [],
  );

  const copy = (index: number, code: string) => {
    navigator.clipboard
      .writeText(code)
      .then(() => {
        setCopied(index);
        if (resetTimer.current) {
          clearTimeout(resetTimer.current);
        }
        resetTimer.current = setTimeout(() => setCopied(null), 2000);
      })
      .catch(() => toast.error('Could not copy to clipboard'));
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-2xl" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>{title}</DialogTitle>
          <DialogDescription className={description ? undefined : 'sr-only'}>{description || title}</DialogDescription>
        </DialogHeader>

        <ol className="flex max-h-[65vh] flex-col gap-6 overflow-y-auto p-4">
          {steps.map((step, index) => (
            <li key={step.title} className="flex gap-3">
              <span className="bg-muted text-muted-foreground flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-medium">
                {index + 1}
              </span>
              <div className="flex min-w-0 flex-1 flex-col gap-2">
                <p className="text-sm font-medium">{step.title}</p>
                {step.description && <p className="text-muted-foreground text-sm">{step.description}</p>}
                {step.code && (
                  <div className="relative">
                    <pre className="bg-muted/50 overflow-x-auto rounded-md border p-3 pr-10 font-mono text-xs leading-relaxed">{step.code}</pre>
                    <Button
                      type="button"
                      size="icon"
                      variant="ghost"
                      className="absolute top-1.5 right-1.5 size-7"
                      onClick={() => copy(index, step.code!)}
                      aria-label={`Copy ${step.title} commands`}
                    >
                      {copied === index ? <CheckIcon className="text-success" /> : <CopyIcon />}
                    </Button>
                  </div>
                )}
              </div>
            </li>
          ))}
        </ol>

        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Close</Button>
          </DialogClose>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
