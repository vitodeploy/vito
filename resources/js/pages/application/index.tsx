import { Head, usePage } from '@inertiajs/react';
import { Site } from '@/types/site';
import AppWithDeployment from '@/pages/application/components/app-with-deployment';
import LoadBalancer from '@/pages/application/components/load-balancer';
import siteHelper from '@/lib/site-helper';
import ServerLayout from '@/layouts/server/layout';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import HeadingSmall from '@/components/heading-small';
import SiteBanners from '@/components/site-banners';
import Logs from '@/pages/server-logs/components/logs';
import { Server } from '@/types/server';

export default function Application() {
  const page = usePage<{
    server: Server;
    site: Site;
  }>();

  siteHelper.storeSite(page.props.site);

  if (page.props.site.status !== 'ready') {
    const failed = page.props.site.status === 'installation_failed';

    return (
      <ServerLayout>
        <Head title={`${page.props.site.domain} - ${page.props.server.name}`} />

        <Container className="flex max-w-5xl flex-col gap-6 space-y-0">
          <HeaderContainer>
            <Heading
              title={failed ? 'Site installation failed' : 'Installing site'}
              description={
                failed
                  ? 'The installation did not complete. Retry from the banner below; completed steps will be skipped.'
                  : 'Your site is being installed. Here you can see the logs'
              }
            />
          </HeaderContainer>

          <SiteBanners site={page.props.site} />

          <div className="flex flex-col gap-2">
            <HeadingSmall title="Installation logs" />
            <Logs server={page.props.server} site={page.props.site} />
          </div>
        </Container>
      </ServerLayout>
    );
  }

  if (page.props.site.type === 'load-balancer') {
    return <LoadBalancer />;
  }

  return <AppWithDeployment />;
}
