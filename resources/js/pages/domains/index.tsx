import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import AddDomain from '@/pages/domains/components/add-domain';
import { DynamicTable } from '@/components/dynamic-table';
import { DynamicTableData, Row } from '@/types/dynamic-table';
import { Domain } from '@/types/domain';
import { BookOpenIcon, LoaderCircleIcon, MoreVerticalIcon, PlusIcon } from 'lucide-react';
import Layout from '@/layouts/app/layout';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { router, useForm } from '@inertiajs/react';
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
import { useState } from 'react';
import InputError from '@/components/ui/input-error';

function Remove({ domain }: { domain: Domain }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.delete(route('domains.destroy', domain.id), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };
  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem variant="destructive" onSelect={(e) => e.preventDefault()}>
          Remove
        </DropdownMenuItem>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Remove {domain.domain}</DialogTitle>
          <DialogDescription className="sr-only">Remove domain from Vito</DialogDescription>
        </DialogHeader>
        <div className="space-y-2 p-4">
          <p>
            Are you sure you want to remove <strong>{domain.domain}</strong> from Vito?
          </p>
          <p className="text-muted-foreground text-sm">This will only remove the domain from Vito, not from your DNS provider.</p>
          <InputError message={form.errors.domain} />
        </div>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button variant="destructive" disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Remove
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

type Page = {
  domains: DynamicTableData;
};

export default function Domains() {
  const page = usePage<Page>();

  return (
    <Layout>
      <Head title="Domains" />
      <Container className="max-w-5xl">
        <div className="flex items-start justify-between">
          <Heading title="Domains" description="All of the domains of your project listed here" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/domains" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <AddDomain>
              <Button>
                <PlusIcon />
                Add Domain
              </Button>
            </AddDomain>
          </div>
        </div>
        <DynamicTable
          tableData={page.props.domains}
          actions={(row: Row) => (
            <div className="flex items-center justify-end gap-2">
              <DropdownMenu modal={false}>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" className="h-8 w-8 p-0">
                    <span className="sr-only">Open menu</span>
                    <MoreVerticalIcon />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <DropdownMenuItem onSelect={() => router.visit(route('domains.show', row.id as number))}>Manage Records</DropdownMenuItem>
                  <DropdownMenuSeparator />
                  <Remove domain={row as unknown as Domain} />
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          )}
        />
      </Container>
    </Layout>
  );
}
