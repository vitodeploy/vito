import { ColumnDef, Row } from '@tanstack/react-table';
import { Button } from '@/components/ui/button';
import { EyeIcon, LoaderCircleIcon } from 'lucide-react';
import type { ServerLog } from '@/types/server-log';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { useState } from 'react';
import axios from 'axios';
import { ScrollArea, ScrollBar } from '@/components/ui/scroll-area';
import DateTime from '@/components/date-time';

const LogActionCell = ({ row }: { row: Row<ServerLog> }) => {
  const [open, setOpen] = useState(false);
  const [loading, setLoading] = useState(false);
  const [content, setContent] = useState('');

  const showLog = async () => {
    setLoading(true);
    try {
      const response = await axios.get(route('logs.show', { server: row.original.server_id, log: row.original.id }));
      setContent(response.data);
    } catch (error: unknown) {
      console.error(error);
      if (error instanceof Error) {
        setContent(error.message);
      } else {
        setContent('An unknown error occurred.');
      }
    } finally {
      setLoading(false);
      setOpen(true);
    }
  };

  return (
    <div className="flex items-center justify-end">
      <Dialog open={open} onOpenChange={setOpen}>
        <DialogTrigger asChild>
          <Button variant="outline" size="sm" onClick={showLog} disabled={loading}>
            {loading ? <LoaderCircleIcon className="animate-spin" /> : <EyeIcon />}
          </Button>
        </DialogTrigger>
        <DialogContent className="sm:max-w-5xl">
          <DialogHeader>
            <DialogTitle>View Log</DialogTitle>
            <DialogDescription className="sr-only">This is all content of the log</DialogDescription>
          </DialogHeader>
          <ScrollArea className="bg-accent text-accent-foreground relative h-[500px] w-full p-4 font-mono text-sm whitespace-pre-line">
            {content}
            <ScrollBar orientation="vertical" />
          </ScrollArea>
          <DialogFooter>
            <Button variant="outline">Download</Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
};

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
    cell: ({ row }) => <LogActionCell row={row} />,
  },
];
