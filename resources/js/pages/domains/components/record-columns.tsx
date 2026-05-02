import { ColumnDef } from '@tanstack/react-table';
import DateTime from '@/components/date-time';
import { DNSRecord } from '@/types/dns-record';
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
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon, LockIcon, MoreVerticalIcon } from 'lucide-react';
import FormSuccessful from '@/components/form-successful';
import { useState } from 'react';
import InputError from '@/components/ui/input-error';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useClipboard } from '@/hooks/use-clipboard';
import RecordForm from './record-form';

function CopyableText({ text }: { text: string | null | undefined }) {
  const { copied, copy } = useClipboard();

  return (
    <button
      type="button"
      className={`cursor-pointer text-left font-mono text-xs break-all ${copied ? 'text-success' : ''}`}
      onClick={() => copy(text || '')}
    >
      {text}
    </button>
  );
}

function Delete({ record }: { record: DNSRecord }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.delete(route('dns-records.destroy', [record.domain_id, record.id]), {
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
          <DialogTitle>Delete DNS Record</DialogTitle>
          <DialogDescription className="sr-only">Delete DNS record</DialogDescription>
        </DialogHeader>
        <div className="space-y-2 p-4">
          <p>Are you sure you want to delete this DNS record?</p>
          <div className="bg-muted rounded-md p-3">
            <div className="font-mono text-sm">
              <div>
                <strong>Type:</strong> {record.type}
              </div>
              <div>
                <strong>Name:</strong> {record.name}
              </div>
              <div>
                <strong>Content:</strong> {record.content}
              </div>
            </div>
          </div>
          <InputError message={form.errors.record} />
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

interface ProviderConfig {
  proxy_types?: string[];
  supports_created_at?: boolean;
}

export function getColumns(providerConfig?: ProviderConfig, domain?: Domain): ColumnDef<DNSRecord>[] {
  const cols: ColumnDef<DNSRecord>[] = [
    {
      accessorKey: 'type',
      header: 'Type',
      enableColumnFilter: true,
      enableSorting: true,
      cell: ({ row }) => {
        return <Badge variant="outline">{row.original.type}</Badge>;
      },
    },
    {
      accessorKey: 'name',
      header: 'Name',
      enableColumnFilter: true,
      enableSorting: true,
      cell: ({ row }) => {
        return (
          <div className="max-w-[200px] whitespace-normal">
            <CopyableText text={row.original.formatted_name} />
          </div>
        );
      },
    },
    {
      accessorKey: 'content',
      header: 'Content',
      enableColumnFilter: true,
      enableSorting: true,
      cell: ({ row }) => {
        return (
          <div className="flex max-w-xs items-start gap-1.5 whitespace-normal">
            {row.original.proxied && (
              <Tooltip>
                <TooltipTrigger asChild>
                  <LockIcon className="text-warning mt-0.5 size-3.5 shrink-0" />
                </TooltipTrigger>
                <TooltipContent side="top">Proxied (CDN enabled)</TooltipContent>
              </Tooltip>
            )}
            <CopyableText text={row.original.content} />
          </div>
        );
      },
    },
    {
      accessorKey: 'priority',
      header: 'Priority',
      enableColumnFilter: false,
      enableSorting: true,
      cell: ({ row }) => {
        return <span className="text-sm">{row.original.type === 'MX' && row.original.priority != null ? row.original.priority : '-'}</span>;
      },
    },
    {
      accessorKey: 'ttl',
      header: 'TTL',
      enableColumnFilter: true,
      enableSorting: true,
      cell: ({ row }) => {
        return <span className="text-sm">{row.original.ttl === 1 ? 'Auto' : row.original.ttl}</span>;
      },
    },
  ];

  if (providerConfig?.supports_created_at !== false) {
    cols.push({
      accessorKey: 'created_at',
      header: 'Created at',
      enableColumnFilter: true,
      enableSorting: true,
      cell: ({ row }) => {
        return <DateTime date={row.original.created_at} />;
      },
    });
  }

  cols.push({
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
              {domain && (
                <RecordForm domain={domain} record={row.original}>
                  <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Edit</DropdownMenuItem>
                </RecordForm>
              )}
              <DropdownMenuSeparator />
              <Delete record={row.original} />
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  });

  return cols;
}
