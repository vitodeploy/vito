import React from 'react';
import { CrownIcon, CopyIcon, SignpostIcon } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { DynamicCellComponentProps } from '../component-registry';

export default function HostedDomainCell({ row }: DynamicCellComponentProps) {
  const domain = row.domain as string;
  const type = row.type as string;
  const truncated = domain.length > 30;
  const label = truncated ? domain.slice(0, 30) + '...' : domain;

  const TypeIcon = type === 'primary' ? CrownIcon : type === 'redirect' ? SignpostIcon : CopyIcon;
  const typeLabel = type === 'primary' ? 'Primary' : type === 'redirect' ? 'Redirect' : 'Alias';

  return (
    <div className="flex items-center gap-2">
      <TooltipProvider>
        <Tooltip>
          <TooltipTrigger asChild>
            <TypeIcon className="text-muted-foreground h-4 w-4 shrink-0 cursor-default" />
          </TooltipTrigger>
          <TooltipContent>{typeLabel}</TooltipContent>
        </Tooltip>
        {truncated ? (
          <Tooltip>
            <TooltipTrigger asChild>
              <span className="cursor-default">{label}</span>
            </TooltipTrigger>
            <TooltipContent>{domain}</TooltipContent>
          </Tooltip>
        ) : (
          <span>{label}</span>
        )}
      </TooltipProvider>
    </div>
  );
}
