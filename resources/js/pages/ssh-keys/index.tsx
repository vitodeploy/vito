import SettingsLayout from '@/layouts/settings/layout';
import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { SshKey } from '@/types/ssh-key';
import AddSshKey from '@/pages/ssh-keys/components/add-ssh-key';
import { DynamicTable } from '@/components/dynamic-table';
import { DynamicTableData, Row } from '@/types/dynamic-table';
import { BookOpenIcon, LoaderCircleIcon, MoreVerticalIcon, PlusIcon } from 'lucide-react';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
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
import { useForm } from '@inertiajs/react';
import FormSuccessful from '@/components/form-successful';
import { useState } from 'react';

function Delete({ sshKey }: { sshKey: SshKey }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.delete(route('ssh-keys.destroy', sshKey.id), {
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
          <DialogTitle>Delete {sshKey.name}</DialogTitle>
          <DialogDescription className="sr-only">Delete ssh key</DialogDescription>
        </DialogHeader>
        <p className="p-4">Are you sure you want to delete this key?</p>
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

type Page = {
  sshKeys: DynamicTableData;
};

export default function SshKeys() {
  const page = usePage<Page>();

  return (
    <SettingsLayout>
      <Head title="SSH Keys" />
      <Container className="max-w-5xl">
        <div className="flex items-start justify-between">
          <Heading title="SSH Keys" description="Here you can manage all of your ssh keys" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/ssh-keys" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <AddSshKey>
              <Button>
                <PlusIcon />
                Add
              </Button>
            </AddSshKey>
          </div>
        </div>

        <DynamicTable
          tableData={page.props.sshKeys}
          actions={(row: Row) => (
            <div className="flex items-center justify-end">
              <DropdownMenu modal={false}>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" className="h-8 w-8 p-0">
                    <span className="sr-only">Open menu</span>
                    <MoreVerticalIcon />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <Delete sshKey={row as unknown as SshKey} />
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          )}
        />
      </Container>
    </SettingsLayout>
  );
}
