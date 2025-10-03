import { useState } from 'react';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { Badge } from '@/components/ui/badge';
import { toast } from 'sonner';
import { CheckCircle2Icon } from 'lucide-react';

export default function CopyableBadge({ text, tooltip }: { text: string | null | undefined; tooltip?: boolean }) {
  const [copySuccess, setCopySuccess] = useState(false);
  const copyToClipboard = () => {
    navigator.clipboard.writeText(text || '').then(() => {
      setCopySuccess(true);
      toast(
        <div className="flex items-center gap-2">
          <CheckCircle2Icon className="text-success size-5" />
          Copied to clipboard!
        </div>,
      );
      setTimeout(() => {
        setCopySuccess(false);
      }, 2000);
    });
  };

  return (
    <Tooltip>
      <TooltipTrigger asChild>
        <div className="inline-flex cursor-pointer justify-start space-x-2 truncate" onClick={copyToClipboard}>
          <Badge variant={copySuccess ? 'success' : 'outline'} className="block max-w-[200px] overflow-hidden overflow-ellipsis">
            {text}
          </Badge>
        </div>
      </TooltipTrigger>
      <TooltipContent side="top">
        <span className="flex items-center space-x-2">{tooltip ? text : 'Copy'}</span>
      </TooltipContent>
    </Tooltip>
  );
}
