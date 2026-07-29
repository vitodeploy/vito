import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, MoreVerticalIcon, PlusIcon } from 'lucide-react';
import { VitoTable } from '@/components/vito-table';
import { useRealtimeRecord } from '@/hooks/use-socket-events';
import NetworkLayout from '@/layouts/network/layout';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import type { InertiaTableData, Row } from '@forjedio/inertia-table-react';
import { asRow } from '@/lib/inertia-table';
import { useDialog } from '@/hooks/use-dialog';
import { Network, NetworkPeer } from '@/types/network';

export default function NetworkPeers() {
  const page = usePage<{
    network: Network;
    peers: InertiaTableData;
  }>();
  const dialog = useDialog();
  const network = useRealtimeRecord<Network>(page.props.network, 'network')!;

  return (
    <NetworkLayout>
      <Head title={`Peers - ${network.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Peers" description="Devices that connect to this network, such as laptops or CI runners" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/networks/peers" target="_blank" rel="noreferrer">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <Button onClick={() => dialog.networkAddPeer.open({ networkId: network.id })}>
              <PlusIcon />
              <span className="hidden lg:block">Add peer</span>
            </Button>
          </div>
        </HeaderContainer>

        <VitoTable
          tableData={page.props.peers}
          actions={(row: Row) => {
            const peer = asRow<NetworkPeer>(row, ['id', 'name', 'status', 'byo', 'has_private_key']);
            const disabled = peer.status === 'disabled';
            return (
              <div className="flex items-center justify-end">
                <DropdownMenu modal={false}>
                  <DropdownMenuTrigger asChild>
                    <Button variant="ghost" className="h-8 w-8 p-0">
                      <span className="sr-only">Open menu</span>
                      <MoreVerticalIcon />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end">
                    <DropdownMenuItem
                      onSelect={() => dialog.networkPeerConfig.open({ networkId: network.id, peerId: peer.id, byo: peer.byo, name: peer.name })}
                    >
                      Show config
                    </DropdownMenuItem>
                    <DropdownMenuItem
                      onSelect={() =>
                        dialog.confirm.open({
                          title: 'Regenerate keys',
                          description:
                            'Generate a new key pair for this peer. Its current configuration will stop working until the peer is reconfigured.',
                          confirmLabel: 'Regenerate',
                          method: 'post',
                          url: route('networks.peers.regenerate', { network: network.id, networkPeer: peer.id }),
                        })
                      }
                    >
                      Regenerate keys
                    </DropdownMenuItem>
                    <DropdownMenuItem
                      onSelect={() =>
                        dialog.confirm.open({
                          title: disabled ? 'Enable peer' : 'Disable peer',
                          description: disabled
                            ? 'Re-add this peer to every member configuration.'
                            : 'Remove this peer from every member configuration. Its IP address stays reserved.',
                          confirmLabel: disabled ? 'Enable' : 'Disable',
                          method: 'put',
                          url: route('networks.peers.update', { network: network.id, networkPeer: peer.id }),
                          data: { name: peer.name, enabled: disabled },
                        })
                      }
                    >
                      {disabled ? 'Enable' : 'Disable'}
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                      variant="destructive"
                      onSelect={() =>
                        dialog.confirm.open({
                          title: 'Remove peer',
                          description: 'Remove this peer from the network and every member configuration.',
                          variant: 'destructive',
                          confirmLabel: 'Remove',
                          method: 'delete',
                          url: route('networks.peers.destroy', { network: network.id, networkPeer: peer.id }),
                        })
                      }
                    >
                      Remove
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            );
          }}
        />
      </Container>
    </NetworkLayout>
  );
}
