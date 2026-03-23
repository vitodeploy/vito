import ServerLayout from '@/layouts/server/layout';
import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { DynamicTable } from '@/components/dynamic-table';
import { DynamicTableData } from '@/types/dynamic-table';
import { SSL } from '@/types/ssl';
import { Domain } from '@/types/domain';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, LoaderCircleIcon, MoreVerticalIcon } from 'lucide-react';
import CreateServerSsl from '@/pages/server-ssls/components/create-server-ssl';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { View } from '@/pages/server-logs/components/columns';
import ActivateServerSsl from '@/pages/server-ssls/components/activate-server-ssl';
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
import FormSuccessful from '@/components/form-successful';
import InputError from '@/components/ui/input-error';

function Delete({ ssl }: { ssl: SSL }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.delete(route('server-ssls.destroy', { server: ssl.server_id, ssl: ssl.id }), {
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
          <DialogTitle>Delete SSL</DialogTitle>
          <DialogDescription className="sr-only">Delete SSL</DialogDescription>
        </DialogHeader>
        <div className="space-y-2 p-4">
          <p>Are you sure you want to delete this certificate?</p>
          <InputError message={form.errors.ssl} />
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

export default function ServerSsls() {
  const page = usePage<{
    server: Server;
    ssls: DynamicTableData;
    domains: Domain[];
  }>();

  return (
    <ServerLayout>
      <Head title={`SSL - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="SSL" description="Global Server level SSL management for Custom or Wildcard certificates" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/ssl" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <CreateServerSsl server={page.props.server} domains={page.props.domains}>
              <Button>Add</Button>
            </CreateServerSsl>
          </div>
        </HeaderContainer>

        <DynamicTable
          tableData={page.props.ssls}
          actions={(row) => {
            const ssl = row as unknown as SSL;
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
                    {ssl.has_csr && ssl.status === 'created' && (
                      <>
                        <DropdownMenuItem
                          onSelect={() => window.open(route('server-ssls.download', { server: ssl.server_id, ssl: ssl.id }), '_blank')}
                        >
                          Download CSR
                        </DropdownMenuItem>
                        {ssl.type === 'csr' && (
                          <ActivateServerSsl ssl={ssl}>
                            <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Activate</DropdownMenuItem>
                          </ActivateServerSsl>
                        )}
                        <DropdownMenuSeparator />
                      </>
                    )}
                    {ssl.log && (
                      <>
                        <View serverLog={ssl.log}>
                          <DropdownMenuItem onSelect={(e) => e.preventDefault()}>View Log</DropdownMenuItem>
                        </View>
                        <DropdownMenuSeparator />
                      </>
                    )}
                    <Delete ssl={ssl} />
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
