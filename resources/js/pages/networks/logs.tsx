import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon } from 'lucide-react';
import NetworkLayout from '@/layouts/network/layout';
import { DataTable } from '@/components/data-table';
import { networkLogColumns } from '@/pages/networks/components/log-columns';
import { useRealtime, useRealtimeRecord } from '@/hooks/use-socket-events';
import { PaginatedData } from '@/types';
import { ServerLog } from '@/types/server-log';
import { Network } from '@/types/network';

export default function NetworkLogs() {
  const page = usePage<{ network: Network; logs: PaginatedData<ServerLog> }>();
  const network = useRealtimeRecord<Network>(page.props.network, 'network')!;
  const [logs] = useRealtime<ServerLog>(page.props.logs, 'server-log', { network_id: network.id });

  return (
    <NetworkLayout>
      <Head title={`Logs - ${network.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Logs" description="Activity recorded against this network and the servers it runs on" />
          <a href="https://vitodeploy.com/docs/networks/overview" target="_blank" rel="noreferrer">
            <Button variant="outline">
              <BookOpenIcon />
              <span className="hidden lg:block">Docs</span>
            </Button>
          </a>
        </HeaderContainer>

        <DataTable columns={networkLogColumns} paginatedData={logs} searchable sortable />
      </Container>
    </NetworkLayout>
  );
}
