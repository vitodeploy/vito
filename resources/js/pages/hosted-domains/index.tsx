import ServerLayout from '@/layouts/server/layout';
import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import { PaginatedData } from '@/types';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { LockIcon, LockOpenIcon, PlusIcon } from 'lucide-react';
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
  }>();

  const [hostedDomains] = useRealtime<HostedDomain>(page.props.hostedDomains, 'hosted-domain');

  return (
    <ServerLayout>
      <Head title={`Domains - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Domains" description="Manage domains and SSL assignments for this site" />
          <div className="flex items-center gap-2">
            {page.props.site.ssl_enabled ? (
              <Button
                variant="outline"
                onClick={() => router.post(route('sites.disable-ssl', { server: page.props.server.id, site: page.props.site.id }))}
              >
                <LockOpenIcon />
                <span className="hidden lg:block">Disable SSL</span>
              </Button>
            ) : (
              <Button
                variant="outline"
                onClick={() => router.post(route('sites.enable-ssl', { server: page.props.server.id, site: page.props.site.id }))}
              >
                <LockIcon />
                <span className="hidden lg:block">Enable SSL</span>
              </Button>
            )}
            <CreateHostedDomain site={page.props.site}>
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">Add Domain</span>
              </Button>
            </CreateHostedDomain>
          </div>
        </HeaderContainer>

        <DataTable columns={columns} paginatedData={hostedDomains} />
      </Container>
    </ServerLayout>
  );
}
