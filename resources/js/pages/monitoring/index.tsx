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
import Filter from '@/pages/monitoring/components/filter';
import { useState } from 'react';
import { Metric, MetricsFilter } from '@/types/metric';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { kbToGb, mbToGb } from '@/lib/utils';
import Actions from '@/pages/monitoring/components/actions';
import { Alert, AlertDescription } from '@/components/ui/alert';

export default function Monitoring() {
  const page = usePage<{
    server: Server;
    lastMetric?: Metric;
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

        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
          <Card>
            <CardHeader>
              <CardTitle>Memory details</CardTitle>
              <CardDescription className="sr-only">Memory details</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="flex items-center justify-between border-b p-4">
                <span>Used</span>
                <span>{page.props.lastMetric ? kbToGb(page.props.lastMetric.memory_used) + ' GB' : 'N/A'}</span>
              </div>
              <div className="flex items-center justify-between border-b p-4">
                <span>Free</span>
                <span>{page.props.lastMetric ? kbToGb(page.props.lastMetric.memory_free) + ' GB' : 'N/A'}</span>
              </div>
              <div className="flex items-center justify-between p-4">
                <span>Total</span>
                <span>{page.props.lastMetric ? kbToGb(page.props.lastMetric.memory_total) + ' GB' : 'N/A'}</span>
              </div>
            </CardContent>
          </Card>
          <Card>
            <CardHeader>
              <CardTitle>Disk details</CardTitle>
              <CardDescription className="sr-only">Disk details</CardDescription>
            </CardHeader>
            <CardContent>
              <div className="flex items-center justify-between border-b p-4">
                <span>Used</span>
                <span>{page.props.lastMetric ? mbToGb(page.props.lastMetric.disk_used) + ' GB' : 'N/A'}</span>
              </div>
              <div className="flex items-center justify-between border-b p-4">
                <span>Free</span>
                <span>{page.props.lastMetric ? mbToGb(page.props.lastMetric.disk_free) + ' GB' : 'N/A'}</span>
              </div>
              <div className="flex items-center justify-between p-4">
                <span>Total</span>
                <span>{page.props.lastMetric ? mbToGb(page.props.lastMetric.disk_total) + ' GB' : 'N/A'}</span>
              </div>
            </CardContent>
          </Card>
        </div>
      </Container>
    </ServerLayout>
  );
}
