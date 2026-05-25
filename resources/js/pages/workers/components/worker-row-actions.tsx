import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import FormSuccessful from '@/components/form-successful';
import { useState } from 'react';
import { Worker } from '@/types/worker';
import LogOutput from '@/components/log-output';
import { useQuery } from '@tanstack/react-query';
import axios from 'axios';

export function WorkerAction({ type, worker }: { type: 'start' | 'stop' | 'restart'; worker: Worker }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.post(route(`workers.${type}`, { server: worker.server_id, worker: worker }), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem onSelect={(e) => e.preventDefault()} className="capitalize">
          {type}
        </DropdownMenuItem>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>
            <span className="capitalize">{type}</span> worker
          </DialogTitle>
          <DialogDescription className="sr-only">{type} worker</DialogDescription>
        </DialogHeader>
        <p className="p-4">Are you sure you want to {type} the worker?</p>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button variant={type === 'stop' ? 'destructive' : 'default'} disabled={form.processing} onClick={submit} className="capitalize">
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            {type}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

export function WorkerLogs({ worker }: { worker: Worker }) {
  const [open, setOpen] = useState(false);

  const query = useQuery({
    queryKey: ['workerLog', worker.id],
    queryFn: async () => {
      const response = await axios.get(route('workers.logs', { server: worker.server_id, worker: worker.id }));
      return response.data.logs;
    },
    refetchInterval: 2500,
    enabled: open,
  });

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Logs</DropdownMenuItem>
      </DialogTrigger>
      <DialogContent className="sm:max-w-5xl">
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
