import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import LogOutput from '@/components/log-output';
import { Service } from '@/types/service';
import { ReactNode, useState } from 'react';
import { useLogContent } from '@/hooks/use-log-content';

export default function InstallationLog({ service, children }: { service: Service; children?: ReactNode }) {
  const [open, setOpen] = useState(false);

  const { content, isLoading, error } = useLogContent({
    serverId: service.server_id,
    logId: service.log?.id ?? 0,
    enabled: open && !!service.log,
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
            {isLoading && 'Loading...'}
            {error && <div className="text-red-500">Error: {error}</div>}
            {content && !error && content}
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
