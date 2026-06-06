import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { Worker } from '@/types/worker';
import { useDialog } from '@/hooks/use-dialog';

export function WorkerAction({ type, worker }: { type: 'start' | 'stop' | 'restart'; worker: Worker }) {
  const dialog = useDialog();

  return (
    <DropdownMenuItem
      className="capitalize"
      onSelect={() =>
        dialog.confirm.open({
          title: `${type} worker`,
          description: `Are you sure you want to ${type} the worker?`,
          variant: type === 'stop' ? 'destructive' : 'default',
          confirmLabel: type,
          method: 'post',
          url: route(`workers.${type}`, { server: worker.server_id, worker: worker }),
        })
      }
    >
      {type}
    </DropdownMenuItem>
  );
}

export function WorkerLogs({ worker }: { worker: Worker }) {
  const dialog = useDialog();

  return <DropdownMenuItem onSelect={() => dialog.workerLogs.open({ serverId: worker.server_id, workerId: worker.id })}>Logs</DropdownMenuItem>;
}

export function WorkerEnvironment({ worker }: { worker: Worker }) {
  const dialog = useDialog();

  return <DropdownMenuItem onSelect={() => dialog.workerEnv.open({ serverId: worker.server_id, workerId: worker.id })}>Environment</DropdownMenuItem>;
}
