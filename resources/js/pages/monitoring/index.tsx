import { Head, Link, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import ServerLayout from '@/layouts/server/layout';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, TriangleAlertIcon } from 'lucide-react';
import Container from '@/components/container';
import MetricsCards from '@/pages/monitoring/components/metrics-cards';
import SystemStatus from '@/pages/monitoring/components/system-status';
import MemoryDiskDetails from '@/pages/monitoring/components/memory-disk-details';
import Filter from '@/pages/monitoring/components/filter';
import { useState } from 'react';
import { MetricsFilter } from '@/types/metric';
import Actions from '@/pages/monitoring/components/actions';
import { Alert, AlertDescription } from '@/components/ui/alert';

export default function Monitoring() {
  const page = usePage<{
    server: Server;
    hasMonitoringService: boolean;
  }>();

  const [filter, setFilter] = useState<MetricsFilter>();

  return (
    <ServerLayout>
      <Head title={`Monitoring - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Monitoring" description="Here you can see your server's metrics" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/monitoring" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <Filter onValueChange={setFilter} />
            <Actions server={page.props.server} />
          </div>
        </HeaderContainer>

        {!page.props.hasMonitoringService && (
          <Alert variant="destructive">
            <TriangleAlertIcon />
            <AlertDescription>
              <p>
                To monitor your server, you need to first install a{' '}
                <Link href={route('services', { server: page.props.server })} className="font-bold underline">
                  monitoring service
                </Link>
                .
              </p>
            </AlertDescription>
          </Alert>
        )}

        {page.props.hasMonitoringService && <SystemStatus server={page.props.server} filter={filter} />}

        <MetricsCards server={page.props.server} filter={filter} />

        <MemoryDiskDetails server={page.props.server} filter={filter} />
      </Container>
    </ServerLayout>
  );
}
