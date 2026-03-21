import ServerLayout from '@/layouts/server/layout';
import SiteBanners from '@/components/site-banners';
import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import { PaginatedData } from '@/types';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, PlusIcon } from 'lucide-react';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/redirects/components/columns';
import { Redirect } from '@/types/redirect';
import CreateRedirect from '@/pages/redirects/components/create-redirect';
import { Site } from '@/types/site';
import { useRealtime } from '@/hooks/use-socket-events';

export default function Redirects() {
  const page = usePage<{
    server: Server;
    site: Site;
    redirects: PaginatedData<Redirect>;
  }>();

  const [redirects] = useRealtime<Redirect>(page.props.redirects, 'redirect');

  return (
    <ServerLayout>
      <Head title={`Redirect - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Redirect" description="Here you can Redirect certificates" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/sites/redirects" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <CreateRedirect site={page.props.site}>
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">Create redirect</span>
              </Button>
            </CreateRedirect>
          </div>
        </HeaderContainer>

        <SiteBanners site={page.props.site} />

        <DataTable columns={columns} paginatedData={redirects} />
      </Container>
    </ServerLayout>
  );
}
