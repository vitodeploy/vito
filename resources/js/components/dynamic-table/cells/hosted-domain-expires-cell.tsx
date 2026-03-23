import React from 'react';
import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { DynamicCellComponentProps } from '../component-registry';
import moment from 'moment';

export default function HostedDomainExpiresCell({ row }: DynamicCellComponentProps) {
  const sslExpiresAt = row.ssl_expires_at as string | null;

  if (!sslExpiresAt) {
    return <span>-</span>;
  }

  const targetDate = moment(sslExpiresAt);
  const today = moment();
  const daysRemaining = targetDate.diff(today, 'days');

  return (
    <TooltipProvider>
      <Tooltip>
        <TooltipTrigger asChild>
          <Badge variant="outline" className="cursor-default">
            {daysRemaining} {daysRemaining === 1 ? 'day' : 'days'}
          </Badge>
        </TooltipTrigger>
        <TooltipContent>{targetDate.format('MMMM D, YYYY')}</TooltipContent>
      </Tooltip>
    </TooltipProvider>
  );
}
