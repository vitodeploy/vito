import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import LogOutput from '@/components/log-output';
import { Service } from '@/types/service';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';
import { ReactNode, useState } from 'react';

export default function InstallationLog({ service, children }: { service: Service; children?: ReactNode }) {
  const [open, setOpen] = useState(false);

  const query = useQuery({
    queryKey: ['service-installation-log', service.id],
    queryFn: async () => {
      if (!service.log) {
        throw new Error('No installation log available');
      }
      try {
        const response = await axios.get(route('logs.show', { server: service.server_id, log: service.log.id }));
        return typeof response.data === 'string' ? response.data : JSON.stringify(response.data, null, 2);
      } catch (error: unknown) {
        if (axios.isAxiosError(error)) {
          throw new Error(error.response?.data?.error || 'An error occurred while fetching the log');
        }
        throw new Error('Unknown error occurred');
      }
    },
    enabled: open && !!service.log,
    retry: false,
    refetchInterval: (query) => {
      if (query.state.status === 'error') return false;
      return 2500;
    },
  });

  if (!service.log) {
    return null;
  }

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        {children ? children : <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Installation Log</DropdownMenuItem>}
      </DialogTrigger>
      <DialogContent className="sm:max-w-5xl">
        <DialogHeader>
          <DialogTitle>Installation Log</DialogTitle>
          <DialogDescription className="sr-only">Installation log for {service.name}</DialogDescription>
        </DialogHeader>
        <LogOutput>
          <>
            {query.isLoading && 'Loading...'}
            {query.isError && <div className="text-red-500">Error: {query.error.message}</div>}
            {query.data && !query.isError && query.data}
          </>
        </LogOutput>
        <DialogFooter>
          <a href={route('logs.download', { server: service.server_id, log: service.log.id })} target="_blank">
            <Button variant="outline">Download</Button>
          </a>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
