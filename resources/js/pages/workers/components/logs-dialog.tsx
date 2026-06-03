import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import LogOutput from '@/components/log-output';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';

type WorkerLogsDialogProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  serverId: number;
  workerId: number;
};

export default function WorkerLogsDialog({ open, onOpenChange, serverId, workerId }: WorkerLogsDialogProps) {
  const query = useQuery({
    queryKey: ['workerLog', workerId],
    queryFn: async () => {
      const response = await axios.get(route('workers.logs', { server: serverId, worker: workerId }));
      return response.data.logs;
    },
    refetchInterval: 2500,
    enabled: open,
  });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-5xl" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Worker logs</DialogTitle>
          <DialogDescription className="sr-only">View worker logs</DialogDescription>
        </DialogHeader>
        <LogOutput>{query.isLoading ? 'Loading...' : query.data}</LogOutput>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Close</Button>
          </DialogClose>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
