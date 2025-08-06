import React, { useState, useRef } from 'react';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { Badge } from '@/components/ui/badge';
import { copyToClipboard } from '@/lib/utils';

export default function CopyableBadge({ text }: { text: string }) {
  const [copySuccess, setCopySuccess] = useState(false);
  const hiddenInputRef = useRef<HTMLInputElement>(null);
  
  const handleCopy = async () => {
    const success = await copyToClipboard(text, hiddenInputRef.current || undefined);
    setCopySuccess(true);
    setTimeout(() => {
      setCopySuccess(false);
    }, 2000);
  };

  return (
    <>
      <input
        ref={hiddenInputRef}
        value={text}
        style={{ position: 'absolute', left: '-9999px', opacity: 0, pointerEvents: 'none' }}
        readOnly
        tabIndex={-1}
      />
      <Tooltip>
        <TooltipTrigger asChild>
          <div className="inline-flex cursor-pointer justify-start space-x-2 truncate" onClick={handleCopy}>
            <Badge variant={copySuccess ? 'success' : 'outline'} className="block max-w-[200px] overflow-ellipsis">
              {text}
            </Badge>
          </div>
        </TooltipTrigger>
        <TooltipContent side="top">
          <span className="flex items-center space-x-2">Copy</span>
        </TooltipContent>
      </Tooltip>
    </>
  );
}
