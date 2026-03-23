import { Head, Link, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, LoaderCircleIcon, MoreVerticalIcon, PlayIcon, PlusIcon } from 'lucide-react';
import { DynamicTable } from '@/components/dynamic-table';
import { DynamicTableData, Row } from '@/types/dynamic-table';
import { Script } from '@/types/script';
import Layout from '@/layouts/app/layout';
import ScriptForm from '@/pages/scripts/components/form';
import Execute from '@/pages/scripts/components/execute';
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

function Delete({ script }: { script: Script }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.delete(route('scripts.destroy', { script: script.id }), {
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
          <DialogTitle>Delete script</DialogTitle>
          <DialogDescription className="sr-only">Delete script</DialogDescription>
        </DialogHeader>
        <p className="p-4">Are you sure you want to this script?</p>
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

export default function Scripts() {
  const page = usePage<{
    scripts: DynamicTableData;
  }>();

  return (
    <Layout>
      <Head title={`Scripts`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Scripts" description="These are the scripts that you can run on your site's location" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/scripts" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <ScriptForm>
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">Create</span>
              </Button>
            </ScriptForm>
          </div>
        </HeaderContainer>

        <DynamicTable
          tableData={page.props.scripts}
          actions={(row: Row) => (
            <div className="flex items-center justify-end gap-1">
              <Execute script={row as unknown as Script}>
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
                  <ScriptForm script={row as unknown as Script}>
                    <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Edit</DropdownMenuItem>
                  </ScriptForm>
                  <Link
                    href={route('scripts.show', {
                      script: row.id as number,
                    })}
                  >
                    <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Executions</DropdownMenuItem>
                  </Link>
                  <DropdownMenuSeparator />
                  <Delete script={row as unknown as Script} />
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          )}
        />
      </Container>
    </Layout>
  );
}
