import { Head, Link, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import ServerLayout from '@/layouts/server/layout';
import SiteBanners from '@/components/site-banners';
import { BookOpenIcon, LoaderCircleIcon, MoreVerticalIcon, PlayIcon, PlusIcon } from 'lucide-react';
import { DynamicTable } from '@/components/dynamic-table';
import { DynamicTableData, Row } from '@/types/dynamic-table';
import CreateCommand from '@/pages/commands/components/create-command';
import { Command } from '@/types/command';
import { Site } from '@/types/site';
import EditCommand from '@/pages/commands/components/edit-command';
import Execute from '@/pages/commands/components/execute';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
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
import { useForm } from '@inertiajs/react';
import FormSuccessful from '@/components/form-successful';
import { useState } from 'react';

function Delete({ command }: { command: Command }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.delete(route('commands.destroy', { server: command.server_id, site: command.site_id, command: command.id }), {
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
          <DialogTitle>Delete command</DialogTitle>
          <DialogDescription className="sr-only">Delete command</DialogDescription>
        </DialogHeader>
        <p className="p-4">Are you sure you want to this command?</p>
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

export default function Commands() {
  const page = usePage<{
    server: Server;
    site: Site;
    commands: DynamicTableData;
  }>();

  return (
    <ServerLayout>
      <Head title={`Commands - ${page.props.site.domain} - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <div className="flex items-start justify-between">
          <Heading title="Commands" description="These are the commands that you can run on your site's location" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/sites/commands" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <CreateCommand>
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">Create</span>
              </Button>
            </CreateCommand>
          </div>
        </div>

        <SiteBanners site={page.props.site} />

        <DynamicTable
          tableData={page.props.commands}
          actions={(row: Row) => (
            <div className="flex items-center justify-end gap-1">
              <Execute command={row as unknown as Command}>
                <Button variant="outline" className="size-8">
                  <PlayIcon className="size-3" />
                </Button>
              </Execute>
              <DropdownMenu modal={false}>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" className="h-8 w-8 p-0">
                    <span className="sr-only">Open menu</span>
                    <MoreVerticalIcon />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <EditCommand command={row as unknown as Command}>
                    <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Edit</DropdownMenuItem>
                  </EditCommand>
                  <Link
                    href={route('commands.show', {
                      server: row.server_id as number,
                      site: row.site_id as number,
                      command: row.id as number,
                    })}
                  >
                    <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Executions</DropdownMenuItem>
                  </Link>
                  <DropdownMenuSeparator />
                  <Delete command={row as unknown as Command} />
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          )}
        />
      </Container>
    </ServerLayout>
  );
}
