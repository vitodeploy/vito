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
import axios from 'axios';
import DateTime from '@/components/date-time';
import LogOutput from '@/components/log-output';
import { useQuery } from '@tanstack/react-query';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { useForm } from '@inertiajs/react';
import FormSuccessful from '@/components/form-successful';

export function View({ serverLog, children }: { serverLog: ServerLog; children?: ReactNode }) {
  const [open, setOpen] = useState(false);

  const query = useQuery({
    queryKey: ['server-log', serverLog.id],
    queryFn: async () => {
      try {
        const response = await axios.get(route('logs.show', { server: serverLog.server_id, log: serverLog.id }));
        return typeof response.data === 'string' ? response.data : JSON.stringify(response.data, null, 2);
      } catch (error: unknown) {
        if (axios.isAxiosError(error)) {
          throw new Error(error.response?.data?.error || 'An error occurred while fetching the log');
        }
        throw new Error('Unknown error occurred');
      }
    },
    enabled: open,
    retry: false,
    refetchInterval: (query) => {
      if (query.state.status === 'error') return false;
      return 2500;
    },
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
            {query.isLoading && 'Loading...'}
            {query.isError && <div className="text-red-500">Error: {query.error.message}</div>}
            {query.data && !query.isError && query.data}
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
