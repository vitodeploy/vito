import React from 'react';
import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { DynamicCellComponentProps } from '../component-registry';

export default function SslDomainsCell({ row }: DynamicCellComponentProps) {
  const domains = row.domains as string[] | null;

  if (!domains || domains.length === 0) {
    return <span>-</span>;
  }

  return (
    <div className="flex flex-wrap gap-1">
      <TooltipProvider>
        {domains.map((domain) => {
          const truncated = domain.length > 30;
          const label = truncated ? domain.slice(0, 30) + '...' : domain;
          return truncated ? (
            <Tooltip key={domain}>
              <TooltipTrigger asChild>
                <Badge variant="outline" className="cursor-default">
                  {label}
                </Badge>
              </TooltipTrigger>
              <TooltipContent>{domain}</TooltipContent>
            </Tooltip>
          ) : (
            <Badge key={domain} variant="outline">
              {label}
            </Badge>
          );
        })}
      </TooltipProvider>
    </div>
  );
}
