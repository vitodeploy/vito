import { useEffect } from 'react';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { BookOpenIcon, PlusIcon, RefreshCwIcon } from 'lucide-react';
import type { InertiaTableData, Row } from '@forjedio/inertia-table-react';
import { Server } from '@/types/server';
import { Service } from '@/types/service';
import ServerLayout from '@/layouts/server/layout';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import Container from '@/components/container';
import { VitoTable } from '@/components/vito-table';
import { asRow } from '@/lib/inertia-table';
import InstallService from '@/pages/services/components/install';
import ServiceActions from '@/pages/services/components/service-actions';
import { cn } from '@/lib/utils';

type Page = {
  server: Server;
  services: InertiaTableData;
  refreshing: boolean;
};

export default function ServicesIndex() {
  const page = usePage<Page>();
  const { server, services, refreshing } = page.props;

  const form = useForm({});

  useEffect(() => {
    if (!refreshing) {
      return;
    }

    const interval = setInterval(() => router.reload(), 5000);

    return () => clearInterval(interval);
  }, [refreshing]);

  const refresh = () => {
    form.post(route('services.refresh', { server: server.id }), { preserveScroll: true });
  };

  const busy = refreshing || form.processing;

  return (
    <ServerLayout>
      <Head title={`Services - ${server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Services" description="Here you can manage server's services" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/services" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <Button variant="outline" onClick={refresh} disabled={busy}>
              <RefreshCwIcon className={cn(busy && 'animate-spin')} />
              <span className="hidden lg:block">Refresh</span>
            </Button>
            <InstallService>
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">Install</span>
              </Button>
            </InstallService>
          </div>
        </HeaderContainer>

        <VitoTable
          tableData={services}
          actions={(row: Row) => <ServiceActions service={asRow<{ resource: Service }>(row, ['resource']).resource} />}
        />
      </Container>
    </ServerLayout>
  );
}
