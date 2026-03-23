import SettingsLayout from '@/layouts/settings/layout';
import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { DynamicTable } from '@/components/dynamic-table';
import { DynamicTableData } from '@/types/dynamic-table';
import { ApiKey } from '@/types/api-key';
import CreateApiKey from '@/pages/api-keys/components/create-api-key';
import { BookOpenIcon, MoreVerticalIcon, PlusIcon } from 'lucide-react';
import { Project } from '@/types/project';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
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
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';
import { LoaderCircleIcon } from 'lucide-react';
import FormSuccessful from '@/components/form-successful';

function Delete({ apiKey }: { apiKey: ApiKey }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.delete(route('api-keys.destroy', apiKey.id), {
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
          <DialogTitle>Delete {apiKey.name}</DialogTitle>
          <DialogDescription className="sr-only">Delete api key</DialogDescription>
        </DialogHeader>
        <p className="p-4">
          Are you sure you want to delete <strong>{apiKey.name}</strong>?
        </p>
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

export default function ApiKeys() {
  const page = usePage<{
    apiKeys: DynamicTableData;
    projects: Project[];
  }>();

  return (
    <SettingsLayout>
      <Head title="API Keys" />
      <Container className="max-w-5xl">
        <div className="flex items-start justify-between">
          <Heading title="API Keys" description="Here you can manage API keys" />
          <div className="flex items-center gap-2">
            <a href="/api/docs" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                Docs
              </Button>
            </a>
            <CreateApiKey projects={page.props.projects}>
              <Button>
                <PlusIcon />
                Create
              </Button>
            </CreateApiKey>
          </div>
        </div>
        <DynamicTable
          tableData={page.props.apiKeys}
          actions={(row) => {
            const apiKey = row as unknown as ApiKey;
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
                    <Delete apiKey={apiKey} />
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            );
          }}
        />
      </Container>
    </SettingsLayout>
  );
}
