import { ColumnDef } from '@tanstack/react-table';
import { Button } from '@/components/ui/button';
import { LoaderCircleIcon, MoreVerticalIcon } from 'lucide-react';
import type { ServerLog } from '@/types/server-log';
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
import { ReactNode, useState } from 'react';
import DateTime from '@/components/date-time';
import LogOutput from '@/components/log-output';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useForm } from '@inertiajs/react';
import FormSuccessful from '@/components/form-successful';
import { useLogContent } from '@/hooks/use-log-content';

export function View({ serverLog, children }: { serverLog: ServerLog; children?: ReactNode }) {
  const [open, setOpen] = useState(false);

  const { content, isLoading, error } = useLogContent({
    serverId: serverLog.server_id,
    logId: serverLog.id,
    enabled: open,
  });

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children ? children : <DropdownMenuItem onSelect={(e) => e.preventDefault()}>View</DropdownMenuItem>}</DialogTrigger>
      <DialogContent className="sm:max-w-5xl">
        <DialogHeader>
          <DialogTitle>View Log</DialogTitle>
          <DialogDescription className="sr-only">This is all content of the log</DialogDescription>
        </DialogHeader>
        <LogOutput>
          <>
            {isLoading && 'Loading...'}
            {error && <div className="text-red-500">Error: {error}</div>}
            {content && !error && content}
          </>
        </LogOutput>
        <DialogFooter>
          <Download serverLog={serverLog}>
            <Button variant="outline">Download</Button>
          </Download>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

export function Download({ serverLog, children }: { serverLog: ServerLog; children: ReactNode }) {
  return (
    <a href={route('logs.download', { server: serverLog.server_id, log: serverLog.id })} target="_blank">
      {children}
    </a>
  );
}

function Clear({ serverLog }: { serverLog: ServerLog }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.post(route('logs.clear', { server: serverLog.server_id, log: serverLog.id }), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };
  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Clear</DropdownMenuItem>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Clear {serverLog.name}</DialogTitle>
          <DialogDescription className="sr-only">Clear log contents</DialogDescription>
        </DialogHeader>
        <div className="space-y-2 p-4">
          <p>
            Are you sure you want to clear the contents of <strong>{serverLog.name}</strong>?
          </p>
          <p className="text-muted-foreground text-sm">This will remove all content from the log file but keep the file itself.</p>
        </div>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Clear
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function Delete({ serverLog }: { serverLog: ServerLog }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.delete(route('logs.destroy', { server: serverLog.server_id, log: serverLog.id }), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };
  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem variant="destructive" onSelect={(e) => e.preventDefault()}>
          Delete
        </DropdownMenuItem>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete {serverLog.name}</DialogTitle>
          <DialogDescription className="sr-only">Delete log</DialogDescription>
        </DialogHeader>
        <div className="space-y-2 p-4">
          <p>
            Are you sure you want to delete <strong>{serverLog.name}</strong>?
          </p>
        </div>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button variant="destructive" disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Delete
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

export const columns: ColumnDef<ServerLog>[] = [
  {
    accessorKey: 'name',
    header: 'Event',
    enableColumnFilter: true,
  },
  {
    accessorKey: 'created_at',
    header: 'Created At',
    enableSorting: true,
    cell: ({ row }) => {
      return <DateTime date={row.original.created_at} />;
    },
  },
  {
    id: 'actions',
    enableColumnFilter: false,
    enableSorting: false,
    cell: ({ row }) => {
      return (
        <div className="flex items-center justify-end">
          <DropdownMenu modal={false}>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                <span className="sr-only">Open menu</span>
                <MoreVerticalIcon />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <View serverLog={row.original} />
              <Download serverLog={row.original}>
                <DropdownMenuItem>Download</DropdownMenuItem>
              </Download>
              <DropdownMenuSeparator />
              {row.original.is_remote && <Clear serverLog={row.original} />}
              <Delete serverLog={row.original} />
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];
