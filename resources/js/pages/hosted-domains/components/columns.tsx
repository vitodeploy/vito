import { ColumnDef } from '@tanstack/react-table';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { CrownIcon, LoaderCircleIcon, MoreVerticalIcon, SignpostIcon, CopyIcon, TriangleAlertIcon } from 'lucide-react';
import React, { useState } from 'react';
import { router, usePage, useForm } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { HostedDomain } from '@/types/hosted-domain';
import { Site } from '@/types/site';
import EditHostedDomain from '@/pages/hosted-domains/components/edit-hosted-domain';
import moment from 'moment';
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

function DeleteHostedDomain({ hostedDomain }: { hostedDomain: HostedDomain }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.delete(
      route('hosted-domains.destroy', {
        server: hostedDomain.server_id,
        site: hostedDomain.site_id,
        hostedDomain: hostedDomain.id,
      }),
      {
        onSuccess: () => {
          setOpen(false);
        },
      },
    );
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
          <DialogTitle>Delete Domain</DialogTitle>
          <DialogDescription className="sr-only">Delete domain</DialogDescription>
        </DialogHeader>
        <div className="space-y-2 p-4">
          <p>
            Are you sure you want to delete <strong>{hostedDomain.domain}</strong>?
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

function ForceActivateHostedDomain({ hostedDomain }: { hostedDomain: HostedDomain }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.post(
      route('hosted-domains.force-activate', {
        server: hostedDomain.server_id,
        site: hostedDomain.site_id,
        hostedDomain: hostedDomain.id,
      }),
      {
        onSuccess: () => {
          setOpen(false);
        },
      },
    );
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Force Activate</DropdownMenuItem>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Force Activate Domain</DialogTitle>
          <DialogDescription className="sr-only">Force activate domain</DialogDescription>
        </DialogHeader>
        <div className="space-y-2 p-4">
          <p>
            The domain <strong>{hostedDomain.domain}</strong> is currently pending because we could not confirm that it resolves to this server. No
            configuration changes have been made yet.
          </p>
          <p>
            If you force activate this domain, the server configuration will be updated regardless. However, this may impact your ability to generate
            an SSL certificate if the domain does not actually point to this server.
          </p>
          <p>Are you sure you want to continue?</p>
        </div>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button variant="destructive" disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Force Activate
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function ErrorIndicator({ error }: { error: string | null }) {
  if (!error) return null;

  return (
    <TooltipProvider delayDuration={0}>
      <Tooltip>
        <TooltipTrigger asChild>
          <div className="bg-destructive/15 text-destructive border-destructive/40 flex cursor-default items-center rounded-md border px-1.5 py-1">
            <TriangleAlertIcon className="h-4 w-4" />
          </div>
        </TooltipTrigger>
        <TooltipContent>{error}</TooltipContent>
      </Tooltip>
    </TooltipProvider>
  );
}

function CertificateCell({ hostedDomain }: { hostedDomain: HostedDomain }) {
  const { ssl_method, ssl_id } = hostedDomain;
  const { site } = usePage<{ site: Site }>().props;
  const createsSiteSSLs = site.webserver_creates_site_ssls;
  const webserverName = site.webserver.charAt(0).toUpperCase() + site.webserver.slice(1);

  if (ssl_method === 'letsencrypt' && !createsSiteSSLs) {
    return <Badge variant="outline">{webserverName} Managed SSL</Badge>;
  }

  if (ssl_method === 'letsencrypt' && createsSiteSSLs && ssl_id) {
    return (
      <TooltipProvider>
        <Tooltip>
          <TooltipTrigger asChild>
            <Badge variant="outline" className="cursor-default">
              Site Certificate
            </Badge>
          </TooltipTrigger>
          <TooltipContent>ID: {ssl_id}</TooltipContent>
        </Tooltip>
      </TooltipProvider>
    );
  }

  if (!ssl_id) {
    return <span>-</span>;
  }

  const sslDomains = hostedDomain.ssl_domains ?? [];

  return (
    <div className="flex flex-wrap gap-1">
      <Badge variant="info">{(hostedDomain.ssl_type ?? '').toUpperCase()}</Badge>
      <Badge variant="info">#{ssl_id}</Badge>
      <TooltipProvider>
        {sslDomains.map((domain) => {
          const truncated = domain.length > 20;
          const label = truncated ? domain.slice(0, 20) + '...' : domain;
          return truncated ? (
            <Tooltip key={domain}>
              <TooltipTrigger asChild>
                <Badge variant="outline" className="cursor-default">
                  {label}
                </Badge>
              </TooltipTrigger>
              <TooltipContent>{domain}</TooltipContent>
            </Tooltip>
          ) : (
            <Badge key={domain} variant="outline">
              {label}
            </Badge>
          );
        })}
      </TooltipProvider>
    </div>
  );
}

export const columns: ColumnDef<HostedDomain>[] = [
  {
    accessorKey: 'domain',
    header: 'Domain',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      const domain = row.original.domain;
      const type = row.original.type;
      const truncated = domain.length > 30;
      const label = truncated ? domain.slice(0, 30) + '...' : domain;

      const TypeIcon = type === 'primary' ? CrownIcon : type === 'redirect' ? SignpostIcon : CopyIcon;
      const typeLabel = type === 'primary' ? 'Primary' : type === 'redirect' ? 'Redirect' : 'Alias';

      return (
        <div className="flex items-center gap-2">
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger asChild>
                <TypeIcon className="text-muted-foreground h-4 w-4 shrink-0 cursor-default" />
              </TooltipTrigger>
              <TooltipContent>{typeLabel}</TooltipContent>
            </Tooltip>
            {truncated ? (
              <Tooltip>
                <TooltipTrigger asChild>
                  <span className="cursor-default">{label}</span>
                </TooltipTrigger>
                <TooltipContent>{domain}</TooltipContent>
              </Tooltip>
            ) : (
              <span>{label}</span>
            )}
          </TooltipProvider>
        </div>
      );
    },
  },
  {
    id: 'ssl',
    header: 'SSL',
    enableColumnFilter: false,
    enableSorting: false,
    cell: ({ row }) => {
      if (row.original.ssl_method === 'none') {
        return <Badge variant="outline">DISABLED</Badge>;
      }
      if (row.original.ssl_method === 'letsencrypt') {
        return <Badge variant="outline">LETSENCRYPT</Badge>;
      }
      return <Badge variant="outline">CUSTOM</Badge>;
    },
  },
  {
    id: 'certificate',
    header: 'Certificate',
    enableColumnFilter: false,
    enableSorting: false,
    cell: ({ row }) => <CertificateCell hostedDomain={row.original} />,
  },
  {
    id: 'expires_at',
    header: 'Expires In',
    enableColumnFilter: false,
    enableSorting: false,
    cell: ({ row }) => {
      if (!row.original.ssl_expires_at) {
        return <span>-</span>;
      }

      const targetDate = moment(row.original.ssl_expires_at);
      const today = moment();
      const daysRemaining = targetDate.diff(today, 'days');

      return (
        <TooltipProvider>
          <Tooltip>
            <TooltipTrigger asChild>
              <Badge variant="outline" className="cursor-default">
                {daysRemaining} {daysRemaining === 1 ? 'day' : 'days'}
              </Badge>
            </TooltipTrigger>
            <TooltipContent>{targetDate.format('MMMM D, YYYY')}</TooltipContent>
          </Tooltip>
        </TooltipProvider>
      );
    },
  },
  {
    accessorKey: 'status',
    header: 'Status',
    enableColumnFilter: true,
    enableSorting: true,
    cell: ({ row }) => {
      return <Badge variant={row.original.status_color}>{row.original.status}</Badge>;
    },
  },
  {
    id: 'actions',
    enableColumnFilter: false,
    enableSorting: false,
    cell: ({ row }) => {
      const isPrimary = row.original.type === 'primary';
      const isProcessing = row.original.status === 'creating' || row.original.status === 'updating' || row.original.status === 'deleting';

      if (isProcessing) {
        return (
          <div className="flex items-center justify-end gap-2">
            <ErrorIndicator error={row.original.error} />
            <div className="flex h-8 w-8 items-center justify-center">
              <LoaderCircleIcon className="text-muted-foreground h-4 w-4 animate-spin" />
            </div>
          </div>
        );
      }

      return (
        <div className="flex items-center justify-end gap-2">
          <ErrorIndicator error={row.original.error} />
          <DropdownMenu modal={false}>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" className="h-8 w-8 p-0">
                <span className="sr-only">Open menu</span>
                <MoreVerticalIcon />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <EditHostedDomain hostedDomain={row.original}>
                <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Edit</DropdownMenuItem>
              </EditHostedDomain>
              {row.original.status === 'pending' && (
                <>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem
                    onSelect={() =>
                      router.post(
                        route('hosted-domains.check-dns', {
                          server: row.original.server_id,
                          site: row.original.site_id,
                          hostedDomain: row.original.id,
                        }),
                      )
                    }
                  >
                    Validate
                  </DropdownMenuItem>
                  <ForceActivateHostedDomain hostedDomain={row.original} />
                </>
              )}
              {!isPrimary && (row.original.status === 'active' || row.original.status === 'pending') && (
                <>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem
                    onSelect={() =>
                      router.post(
                        route('hosted-domains.deactivate', {
                          server: row.original.server_id,
                          site: row.original.site_id,
                          hostedDomain: row.original.id,
                        }),
                      )
                    }
                  >
                    Deactivate
                  </DropdownMenuItem>
                </>
              )}
              {row.original.status === 'inactive' && (
                <>
                  <DropdownMenuSeparator />
                  <DropdownMenuItem
                    onSelect={() =>
                      router.post(
                        route('hosted-domains.reactivate', {
                          server: row.original.server_id,
                          site: row.original.site_id,
                          hostedDomain: row.original.id,
                        }),
                      )
                    }
                  >
                    Reactivate
                  </DropdownMenuItem>
                </>
              )}
              {!isPrimary && (
                <>
                  <DropdownMenuSeparator />
                  <DeleteHostedDomain hostedDomain={row.original} />
                </>
              )}
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      );
    },
  },
];
