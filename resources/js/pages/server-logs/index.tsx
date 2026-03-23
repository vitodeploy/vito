import { Head, usePage } from '@inertiajs/react';
import { ServerLog } from '@/types/server-log';
import { Server } from '@/types/server';
import ServerLayout from '@/layouts/server/layout';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { DynamicTable } from '@/components/dynamic-table';
import { DynamicTableData } from '@/types/dynamic-table';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, MoreVerticalIcon, PlusIcon } from 'lucide-react';
import LogForm from '@/pages/server-logs/components/form';
import { DropdownMenu, DropdownMenuContent, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { View, Download } from '@/pages/server-logs/components/columns';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { useState } from 'react';
import { useForm } from '@inertiajs/react';
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
import { LoaderCircleIcon } from 'lucide-react';
import FormSuccessful from '@/components/form-successful';

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

export default function ServerLogs() {
  const page = usePage<{
    title: string;
    server: Server;
    logs: DynamicTableData;
    remote: boolean;
  }>();

  return (
    <ServerLayout>
      <Head title={`${page.props.title} - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title={page.props.title} description="Here you can see all logs" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/logs" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            {page.props.remote && (
              <LogForm>
                <Button>
                  <PlusIcon />
                  <span className="hidden lg:block">Create</span>
                </Button>
              </LogForm>
            )}
          </div>
        </HeaderContainer>

        <DynamicTable
          tableData={page.props.logs}
          actions={(row) => {
            const serverLog = row as unknown as ServerLog;
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
                    <View serverLog={serverLog} />
                    <Download serverLog={serverLog}>
                      <DropdownMenuItem>Download</DropdownMenuItem>
                    </Download>
                    <DropdownMenuSeparator />
                    {serverLog.is_remote && <Clear serverLog={serverLog} />}
                    <Delete serverLog={serverLog} />
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            );
          }}
        />
      </Container>
    </ServerLayout>
  );
}
