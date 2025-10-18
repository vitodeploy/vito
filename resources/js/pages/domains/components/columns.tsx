import { ColumnDef } from '@tanstack/react-table';
import DateTime from '@/components/date-time';
import { Domain } from '@/types/domain';
import { Badge } from '@/components/ui/badge';
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
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { router, useForm } from '@inertiajs/react';
import { LoaderCircleIcon, MoreVerticalIcon } from 'lucide-react';
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

export const columns: ColumnDef<Domain>[] = [
  {
    accessorKey: 'id',
    header: 'ID',
    enableColumnFilter: true,
    enableSorting: true,
    enableHiding: true,
  },
  {
    accessorKey: 'domain',
    header: 'Domain',
    enableColumnFilter: true,
    enableSorting: true,
  },
  {
    accessorKey: 'dns_provider.name',
    header: 'DNS Provider',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      const dnsProvider = row.original.dns_provider;
      return (
        <div className="flex items-center gap-2">
          <span>{dnsProvider?.name}</span>
          <Badge variant="outline">{dnsProvider?.provider}</Badge>
        </div>
      );
    },
  },
  {
    accessorKey: 'created_at',
    header: 'Added at',
    enableColumnFilter: true,
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
        <div className="flex items-center justify-end gap-2">
          <DropdownMenu modal={false}>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                <span className="sr-only">Open menu</span>
                <MoreVerticalIcon />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem onSelect={() => router.visit(route('domains.show', row.original.id))}>Manage Records</DropdownMenuItem>
              <DropdownMenuSeparator />
              <Remove domain={row.original} />
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];
