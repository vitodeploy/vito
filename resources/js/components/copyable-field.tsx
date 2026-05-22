import { useState } from 'react';
import { CheckIcon, CopyIcon } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export default function CopyableField({ value, className, mono = true }: { value: string; className?: string; mono?: boolean }) {
  const [copied, setCopied] = useState(false);

  const copy = () => {
    navigator.clipboard.writeText(value).then(() => {
      setCopied(true);
      toast.success('Copied to clipboard');
      setTimeout(() => setCopied(false), 1500);
    });
  };

  return (
    <div className={cn('bg-muted/50 flex items-center gap-1 rounded-md border py-1 pr-1 pl-3', className)}>
      <span className={cn('flex-1 truncate text-xs', mono && 'font-mono')}>{value}</span>
      <Button type="button" size="icon" variant="ghost" className="h-6 w-6 shrink-0" onClick={copy} aria-label="Copy">
        {copied ? <CheckIcon className="text-success size-3.5" /> : <CopyIcon className="size-3.5" />}
      </Button>
    </div>
  );
}
