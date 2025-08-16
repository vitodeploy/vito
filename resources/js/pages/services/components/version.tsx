import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import { Service } from '@/types/service';
import { useForm } from '@inertiajs/react';
import { RefreshCwIcon } from 'lucide-react';
import { useState } from 'react';

export default function Version({ service }: { service: Service }) {
  const [fetching, setFetching] = useState(false);

  const form = useForm({});

  const fetch = () => {
    setFetching(true);
    form.get(route('services.version', { server: service.server_id, service: service.id }), {
      onSuccess: () => {
        setFetching(false);
      },
    });
  };

  return (
    <div className="flex items-center gap-2">
      {service.installed_version || service.version}
      <Tooltip>
        <TooltipTrigger asChild>
          <RefreshCwIcon onClick={fetch} className={cn('text-muted-foreground size-4 cursor-pointer', fetching ? 'animate-spin' : '')} />
        </TooltipTrigger>
        <TooltipContent side="right">Fetch version</TooltipContent>
      </Tooltip>
    </div>
  );
}
