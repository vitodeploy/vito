import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import ServerLayout from '@/layouts/server/layout';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon } from 'lucide-react';
import Container from '@/components/container';
import Filter from '@/pages/monitoring/components/filter';
import { useState } from 'react';
import { MetricsFilter } from '@/types/metric';
import CpuView from '@/pages/monitoring/components/cpu-view';
import MemoryView from '@/pages/monitoring/components/memory-view';
import DiskView from '@/pages/monitoring/components/disk-view';

const titles: Record<string, { title: string; description: string }> = {
  load: { title: 'CPU', description: "You're viewing CPU usage, load average, and steal" },
  memory: { title: 'Memory', description: "You're viewing memory and swap usage" },
  disk: { title: 'Disk', description: "You're viewing disk capacity and usage trends" },
};

export default function Show() {
  const page = usePage<{
    server: Server;
    metric: string;
  }>();

  const [filter, setFilter] = useState<MetricsFilter>();
  const meta = titles[page.props.metric] ?? { title: page.props.metric, description: '' };

  return (
    <ServerLayout>
      <Head title={`Monitoring - ${meta.title} - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title={meta.title} description={meta.description} />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/monitoring" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <Filter onValueChange={setFilter} />
          </div>
        </HeaderContainer>

        {page.props.metric === 'load' && <CpuView server={page.props.server} filter={filter} />}
        {page.props.metric === 'memory' && <MemoryView server={page.props.server} filter={filter} />}
        {page.props.metric === 'disk' && <DiskView server={page.props.server} filter={filter} />}
      </Container>
    </ServerLayout>
  );
}
