import { Head, usePage } from '@inertiajs/react';
import { Site } from '@/types/site';
import AppWithDeployment from '@/pages/application/components/app-with-deployment';
import LoadBalancer from '@/pages/application/components/load-balancer';
import siteHelper from '@/lib/site-helper';
import ServerLayout from '@/layouts/server/layout';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import Logs from '@/pages/server-logs/components/logs';
import { Server } from '@/types/server';

export default function Application() {
  const page = usePage<{
    server: Server;
    site: Site;
  }>();

  siteHelper.storeSite(page.props.site);

  if (page.props.site.status !== 'ready') {
    return (
      <ServerLayout>
        <Head title={`${page.props.site.domain} - ${page.props.server.name}`} />

        <Container className="max-w-5xl">
          <HeaderContainer>
            <Heading title="Installing site" description="Your site is being installed. Here you can see the logs" />
          </HeaderContainer>

          <Logs server={page.props.server} site={page.props.site} />
        </Container>
      </ServerLayout>
    );
  }

  if (page.props.site.type === 'load-balancer') {
    return <LoadBalancer />;
  }

  return <AppWithDeployment />;
}
