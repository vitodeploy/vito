import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, MoreVerticalIcon, PlusIcon, RefreshCwIcon } from 'lucide-react';
import { VitoTable } from '@/components/vito-table';
import { useRealtimeRecord } from '@/hooks/use-socket-events';
import NetworkLayout from '@/layouts/network/layout';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import type { InertiaTableData, Row } from '@forjedio/inertia-table-react';
import { asRow } from '@/lib/inertia-table';
import { useDialog } from '@/hooks/use-dialog';
import { Network, NetworkMemberIp, NetworkServer, NetworkServerOption } from '@/types/network';

export default function NetworkServers() {
  const page = usePage<{
    network: Network;
    members: InertiaTableData;
    servers: NetworkServerOption[];
    memberIps: NetworkMemberIp[];
  }>();
  const dialog = useDialog();
  const network = useRealtimeRecord<Network>(page.props.network, 'network')!;
  const isCustom = network.type_value === 'custom';
  const isWireGuard = network.type_value === 'wireguard';
  const isManaged = network.type_value === 'provider';

  return (
    <NetworkLayout>
      <Head title={`Servers - ${network.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Servers" description="Servers connected to this network" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/networks/servers" target="_blank" rel="noreferrer">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <Button
              variant="outline"
              onClick={() =>
                dialog.confirm.open({
                  title: `Sync network [${network.name}]`,
                  description: isManaged
                    ? "Query the provider and update this network's members to match. The network is removed if it no longer exists at the provider."
                    : 'Re-apply configuration to every server in this network.',
                  confirmLabel: 'Sync',
                  method: 'post',
                  url: route('networks.sync', { network: network.id }),
                })
              }
            >
              <RefreshCwIcon />
              <span className="hidden lg:block">Sync</span>
            </Button>
            {!isManaged && (
              <Button onClick={() => dialog.networkAddServer.open({ networkId: network.id, isCustom, servers: page.props.servers })}>
                <PlusIcon />
                <span className="hidden lg:block">Add server</span>
              </Button>
            )}
          </div>
        </HeaderContainer>

        <VitoTable
          tableData={page.props.members}
          actions={(row: Row) => {
            const member = asRow<NetworkServer>(row, ['id', 'server_id']);

            // Every action here is rejected server-side on a provider-managed network.
            if (isManaged) {
              return null;
            }

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
                    {isCustom && (
                      <DropdownMenuItem
                        onSelect={() => {
                          const memberIp = page.props.memberIps.find((m) => m.id === member.id);
                          if (memberIp) {
                            dialog.networkEditServer.open({ networkId: network.id, member: memberIp });
                          }
                        }}
                      >
                        Edit
                      </DropdownMenuItem>
                    )}
                    {isWireGuard && (
                      <DropdownMenuItem
                        onSelect={() =>
                          dialog.confirm.open({
                            title: 'Regenerate configuration',
                            description: 'Re-apply the network configuration to this server.',
                            confirmLabel: 'Regenerate',
                            method: 'post',
                            url: route('networks.servers.sync', { network: network.id, networkServer: member.id }),
                          })
                        }
                      >
                        Regenerate
                      </DropdownMenuItem>
                    )}
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                      variant="destructive"
                      onSelect={() =>
                        dialog.confirm.open({
                          title: 'Remove server',
                          description: 'Remove this server from the network and tear down its configuration.',
                          variant: 'destructive',
                          confirmLabel: 'Remove',
                          method: 'delete',
                          url: route('networks.servers.destroy', { network: network.id, networkServer: member.id }),
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
