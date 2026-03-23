import ServerLayout from '@/layouts/server/layout';
import SiteBanners from '@/components/site-banners';
import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import { PaginatedData } from '@/types';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { BookOpenIcon, EllipsisVerticalIcon, LockIcon, LockOpenIcon, PlusIcon, RefreshCwIcon, ShieldCheckIcon, ShieldOffIcon } from 'lucide-react';
import { router } from '@inertiajs/react';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/hosted-domains/components/columns';
import { HostedDomain } from '@/types/hosted-domain';
import { Site } from '@/types/site';
import CreateHostedDomain from '@/pages/hosted-domains/components/create-hosted-domain';
import { useRealtime } from '@/hooks/use-socket-events';

export default function HostedDomains() {
  const page = usePage<{
    server: Server;
    site: Site;
    hostedDomains: PaginatedData<HostedDomain>;
    hasSiteSsl: boolean;
  }>();

  const [hostedDomains] = useRealtime<HostedDomain>(page.props.hostedDomains, 'hosted-domain');

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
            <CreateHostedDomain site={page.props.site}>
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">Add Domain</span>
              </Button>
            </CreateHostedDomain>
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
          </div>
        </HeaderContainer>

        <SiteBanners site={page.props.site} />

        <DataTable columns={columns} paginatedData={hostedDomains} />
      </Container>
    </ServerLayout>
  );
}
