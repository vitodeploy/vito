import { Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { Server } from '@/types/server';
import { ServerIpAddress } from '@/types/server-ip';
import ServerLayout from '@/layouts/server/layout';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { MoreVerticalIcon, PlusIcon, RefreshCwIcon } from 'lucide-react';
import Container from '@/components/container';
import { VitoTable } from '@/components/vito-table';
import Delete from '@/pages/server-network/components/delete';
import SetPrimary from '@/pages/server-network/components/set-primary';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import type { InertiaTableData, Row } from '@forjedio/inertia-table-react';
import { asRow } from '@/lib/inertia-table';
import { useDialog } from '@/hooks/use-dialog';

export default function ServerNetwork() {
  const page = usePage<{
    server: Server;
    ipAddresses: InertiaTableData;
    interfaces: string[];
  }>();
  const dialog = useDialog();
  const [refreshing, setRefreshing] = useState(false);

  return (
    <ServerLayout>
      <Head title={`Network - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Network" description="Here you can manage the IP addresses configured on the server" />
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              disabled={refreshing}
              onClick={() =>
                router.post(
                  route('servers.network.refresh', { server: page.props.server.id }),
                  {},
                  {
                    preserveScroll: true,
                    onStart: () => setRefreshing(true),
                    onFinish: () => setRefreshing(false),
                  },
                )
              }
            >
              <RefreshCwIcon className={refreshing ? 'animate-spin' : undefined} />
              <span className="hidden lg:block">Refresh</span>
            </Button>
            <Button onClick={() => dialog.serverIpForm.open({ serverId: page.props.server.id, interfaces: page.props.interfaces })}>
              <PlusIcon />
              <span className="hidden lg:block">Add IP</span>
            </Button>
          </div>
        </HeaderContainer>

        <VitoTable
          tableData={page.props.ipAddresses}
          actions={(row: Row) => {
            const ipAddress = asRow<ServerIpAddress>(row, ['id', 'ip', 'server_id', 'is_managed', 'is_primary']);
            const canSetPrimary = !ipAddress.is_primary;
            const canDelete = ipAddress.is_managed && !ipAddress.is_primary;
            const hasActions = canSetPrimary || canDelete;
            return (
              <div className="flex items-center justify-end">
                <DropdownMenu modal={false}>
                  <DropdownMenuTrigger asChild>
                    <Button variant="ghost" className="h-8 w-8 p-0" disabled={!hasActions}>
                      <span className="sr-only">Open menu</span>
                      <MoreVerticalIcon />
                    </Button>
                  </DropdownMenuTrigger>
                  {hasActions && (
                    <DropdownMenuContent align="end">
                      {canSetPrimary && <SetPrimary ipAddress={ipAddress} />}
                      {canDelete && <Delete ipAddress={ipAddress} />}
                    </DropdownMenuContent>
                  )}
                </DropdownMenu>
              </div>
            );
          }}
        />
      </Container>
    </ServerLayout>
  );
}
