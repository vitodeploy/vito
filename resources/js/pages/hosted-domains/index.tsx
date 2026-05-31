import ServerLayout from '@/layouts/server/layout';
import SiteBanners from '@/components/site-banners';
import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import {
  BookOpenIcon,
  EllipsisVerticalIcon,
  LoaderCircleIcon,
  LockIcon,
  LockOpenIcon,
  MoreVerticalIcon,
  PlusIcon,
  RefreshCwIcon,
  ShieldCheckIcon,
  ShieldOffIcon,
} from 'lucide-react';
import { router } from '@inertiajs/react';
import { VitoTable } from '@/components/vito-table';
import ErrorIndicator from '@/components/error-indicator';
import { Site } from '@/types/site';
import { HostedDomain } from '@/types/hosted-domain';
import { useDialog } from '@/hooks/use-dialog';
import type { InertiaTableData, CellRenderProps, Row } from '@forjedio/inertia-table-react';

function CertificateCell({ row }: { row: HostedDomain }) {
  const ssl = row.ssl as { id: number; type: string; domains: string[]; expires_at: string } | null;
  const { ssl_method } = row;
  const { site } = usePage<{ site: Site }>().props;
  const createsSiteSSLs = site.webserver_creates_site_ssls;
  const webserverName = site.webserver.charAt(0).toUpperCase() + site.webserver.slice(1);

  if (ssl_method === 'letsencrypt' && !createsSiteSSLs) {
    return <Badge variant="outline">{webserverName} Managed SSL</Badge>;
  }

  if (ssl_method === 'letsencrypt' && createsSiteSSLs && ssl?.id) {
    return (
      <TooltipProvider>
        <Tooltip>
          <TooltipTrigger asChild>
            <Badge variant="outline" className="cursor-default">
              Site Certificate
            </Badge>
          </TooltipTrigger>
          <TooltipContent>ID: {ssl.id}</TooltipContent>
        </Tooltip>
      </TooltipProvider>
    );
  }

  if (!ssl) {
    return <span>-</span>;
  }

  const sslDomains = ssl.domains ?? [];

  return (
    <div className="flex flex-wrap gap-1">
      <Badge variant="info">{(ssl.type ?? '').toUpperCase()}</Badge>
      <Badge variant="info">#{ssl.id}</Badge>
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

export default function HostedDomains() {
  const page = usePage<{
    server: Server;
    site: Site;
    hostedDomains: InertiaTableData;
    hasSiteSsl: boolean;
  }>();
  const dialog = useDialog();

  const sslLocked = !page.props.site.can_configure_ssl;

  return (
    <ServerLayout>
      <Head title={`Domains - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Domains" description="Manage domains and SSL assignments for this site" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/sites/domains" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="outline">
                  {page.props.site.ssl_enabled ? <LockIcon /> : <LockOpenIcon />}
                  <EllipsisVerticalIcon />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                {page.props.site.ssl_enabled ? (
                  <DropdownMenuItem
                    disabled={sslLocked}
                    onClick={() => !sslLocked && router.post(route('sites.disable-ssl', { server: page.props.server.id, site: page.props.site.id }))}
                  >
                    <LockOpenIcon />
                    Disable SSL
                  </DropdownMenuItem>
                ) : (
                  <DropdownMenuItem
                    disabled={sslLocked}
                    onClick={() => !sslLocked && router.post(route('sites.enable-ssl', { server: page.props.server.id, site: page.props.site.id }))}
                  >
                    <LockIcon />
                    Enable SSL
                  </DropdownMenuItem>
                )}
                {page.props.site.force_ssl ? (
                  <DropdownMenuItem
                    disabled={sslLocked}
                    onClick={() =>
                      !sslLocked && router.post(route('site-settings.disable-force-ssl', { server: page.props.server.id, site: page.props.site.id }))
                    }
                  >
                    <ShieldOffIcon />
                    Disable Force SSL
                  </DropdownMenuItem>
                ) : (
                  <DropdownMenuItem
                    disabled={sslLocked}
                    onClick={() =>
                      !sslLocked && router.post(route('site-settings.enable-force-ssl', { server: page.props.server.id, site: page.props.site.id }))
                    }
                  >
                    <ShieldCheckIcon />
                    Force SSL
                  </DropdownMenuItem>
                )}
                {page.props.site.webserver_creates_site_ssls && (
                  <DropdownMenuItem
                    disabled={!page.props.hasSiteSsl}
                    onClick={() =>
                      page.props.hasSiteSsl &&
                      router.post(route('hosted-domains.renew-ssl', { server: page.props.server.id, site: page.props.site.id }))
                    }
                  >
                    <RefreshCwIcon />
                    Force Renew SSL
                  </DropdownMenuItem>
                )}
              </DropdownMenuContent>
            </DropdownMenu>
            <Button onClick={() => dialog.createHostedDomain.open({ site: page.props.site })}>
              <PlusIcon />
              <span className="hidden lg:block">Add Domain</span>
            </Button>
          </div>
        </HeaderContainer>

        <SiteBanners site={page.props.site} />

        <VitoTable
          tableData={page.props.hostedDomains}
          cellRenderers={{
            certificate: ({ row }: CellRenderProps) => <CertificateCell row={row as unknown as HostedDomain} />,
          }}
          actions={(row: Row) => {
            const hd = row as unknown as HostedDomain;
            const isPrimary = hd.type === 'primary';
            const isProcessing = hd.status === 'creating' || hd.status === 'updating' || hd.status === 'deleting';

            if (isProcessing) {
              return (
                <div className="flex items-center justify-end gap-2">
                  <ErrorIndicator error={hd.error} label={`Domain "${hd.domain}" error`} />
                  <div className="flex h-8 w-8 items-center justify-center">
                    <LoaderCircleIcon className="text-muted-foreground h-4 w-4 animate-spin" />
                  </div>
                </div>
              );
            }

            return (
              <div className="flex items-center justify-end gap-2">
                <ErrorIndicator error={hd.error} label={`Domain "${hd.domain}" error`} />
                <DropdownMenu modal={false}>
                  <DropdownMenuTrigger asChild>
                    <Button variant="ghost" className="h-8 w-8 p-0">
                      <span className="sr-only">Open menu</span>
                      <MoreVerticalIcon />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end">
                    <DropdownMenuItem onSelect={() => dialog.editHostedDomain.open({ hostedDomain: hd })}>Edit</DropdownMenuItem>
                    {hd.status === 'pending' && (
                      <>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                          onSelect={() =>
                            router.post(
                              route('hosted-domains.check-dns', {
                                server: hd.server_id,
                                site: hd.site_id,
                                hostedDomain: hd.id,
                              }),
                            )
                          }
                        >
                          Validate
                        </DropdownMenuItem>
                        <DropdownMenuItem
                          onSelect={() =>
                            dialog.confirm.open({
                              title: 'Force Validate Domain',
                              description: `The domain ${hd.domain} is currently pending because we could not confirm that it resolves to this server. If you force validate this domain, the server configuration will be updated regardless. This may impact your ability to generate an SSL certificate if the domain does not actually point to this server. Are you sure you want to continue?`,
                              variant: 'destructive',
                              confirmLabel: 'Force Validate',
                              method: 'post',
                              url: route('hosted-domains.force-activate', { server: hd.server_id, site: hd.site_id, hostedDomain: hd.id }),
                            })
                          }
                        >
                          Force Validate
                        </DropdownMenuItem>
                      </>
                    )}
                    {!isPrimary && (hd.status === 'active' || hd.status === 'pending') && (
                      <>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                          onSelect={() =>
                            router.post(
                              route('hosted-domains.deactivate', {
                                server: hd.server_id,
                                site: hd.site_id,
                                hostedDomain: hd.id,
                              }),
                            )
                          }
                        >
                          Deactivate
                        </DropdownMenuItem>
                      </>
                    )}
                    {hd.status === 'inactive' && (
                      <>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                          onSelect={() =>
                            router.post(
                              route('hosted-domains.reactivate', {
                                server: hd.server_id,
                                site: hd.site_id,
                                hostedDomain: hd.id,
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
                        <DropdownMenuItem
                          variant="destructive"
                          onSelect={() =>
                            dialog.confirm.open({
                              title: 'Delete Domain',
                              description: `Are you sure you want to delete ${hd.domain}?`,
                              variant: 'destructive',
                              confirmLabel: 'Delete',
                              method: 'delete',
                              url: route('hosted-domains.destroy', { server: hd.server_id, site: hd.site_id, hostedDomain: hd.id }),
                            })
                          }
                        >
                          Delete
                        </DropdownMenuItem>
                      </>
                    )}
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
