import type { CellComponentProps } from '@forjedio/inertia-table-react';
import { InfoIcon, TriangleAlertIcon } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { Service } from '@/types/service';

export function ServiceNetworkedBadge({ row, value }: CellComponentProps) {
  const label = String(value ?? 'unknown');

  if (label === 'n/a') {
    return <span className="text-muted-foreground">-</span>;
  }

  const service = row.resource as Service | undefined;

  if (!service) {
    return <Badge variant="outline">{label}</Badge>;
  }

  const drifted = service.networking_managed && service.networking_effective !== null && service.networking_effective !== service.networking_enabled;
  const intentWithoutObservation = service.networking_enabled && service.networking_effective === null;

  return (
    <div className="flex items-center gap-1">
      <Badge variant={label === 'yes' ? 'success' : label === 'no' ? 'gray' : 'outline'}>{label}</Badge>
      {drifted && (
        <Tooltip>
          <TooltipTrigger asChild>
            <TriangleAlertIcon className="text-warning size-4" />
          </TooltipTrigger>
          <TooltipContent>Doesn&apos;t match Vito&apos;s setting - open Networking to reapply.</TooltipContent>
        </Tooltip>
      )}
      {intentWithoutObservation && (
        <Tooltip>
          <TooltipTrigger asChild>
            <InfoIcon className="text-muted-foreground size-4" />
          </TooltipTrigger>
          <TooltipContent>Networking is enabled by Vito - live state not checked yet. Use Refresh.</TooltipContent>
        </Tooltip>
      )}
    </div>
  );
}
