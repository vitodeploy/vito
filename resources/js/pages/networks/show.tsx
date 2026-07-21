import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ReactNode } from 'react';
import NetworkLayout from '@/layouts/network/layout';
import { useDialog } from '@/hooks/use-dialog';
import { useRealtimeRecord } from '@/hooks/use-socket-events';
import { Network } from '@/types/network';

function DetailRow({ label, children }: { label: string; children: ReactNode }) {
  return (
    <div className="flex items-center justify-between gap-4 p-4">
      <span className="text-muted-foreground text-sm">{label}</span>
      <span className="text-sm font-medium">{children}</span>
    </div>
  );
}

export default function NetworkOverview() {
  const page = usePage<{ network: Network }>();
  const network = useRealtimeRecord<Network>(page.props.network, 'network')!;
  const dialog = useDialog();
  const isProvider = network.type_value === 'provider';

  return (
    <NetworkLayout>
      <Head title={`Overview - ${network.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Overview" description={network.name} />
          <Button
            variant="outline"
            onClick={() =>
              dialog.confirm.open({
                title: `Sync network [${network.name}]`,
                description: 'Re-apply configuration to every server in this network.',
                confirmLabel: 'Sync',
                method: 'post',
                url: route('networks.sync', { network: network.id }),
              })
            }
          >
            Sync
          </Button>
        </HeaderContainer>

        <div className="rounded-xl border divide-y">
          <DetailRow label="Type">
            <Badge variant={network.type_color}>{network.type}</Badge>
          </DetailRow>
          <DetailRow label="Status">
            <Badge variant={network.status_color}>{network.status}</Badge>
          </DetailRow>
          {network.cidr && <DetailRow label="CIDR">{network.cidr}</DetailRow>}
          {!isProvider && <DetailRow label="Address pool">{network.addressing_pool}</DetailRow>}
          {network.port && <DetailRow label="Listen port">{network.port}</DetailRow>}
          <DetailRow label="Servers">{network.servers_count ?? 0}</DetailRow>
        </div>
      </Container>
    </NetworkLayout>
  );
}
