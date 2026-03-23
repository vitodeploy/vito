import React from 'react';
import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { DynamicCellComponentProps } from '../component-registry';
import moment from 'moment';

export default function SslExpiresCell({ row }: DynamicCellComponentProps) {
  const expiresAt = row.expires_at as string | null;

  if (!expiresAt) {
    return <span>-</span>;
  }

  const targetDate = moment(expiresAt);
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
