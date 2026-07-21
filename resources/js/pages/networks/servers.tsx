import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { MoreVerticalIcon, PlusIcon } from 'lucide-react';
import { VitoTable } from '@/components/vito-table';
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
  const network = page.props.network;
  const isProvider = network.type_value === 'provider';

  return (
    <NetworkLayout>
      <Head title={`Servers - ${network.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Servers" description="Servers connected to this network" />
          <Button onClick={() => dialog.networkAddServer.open({ networkId: network.id, isProvider, servers: page.props.servers })}>
            <PlusIcon />
            <span className="hidden lg:block">Add server</span>
          </Button>
        </HeaderContainer>

        <VitoTable
          tableData={page.props.members}
          actions={(row: Row) => {
            const member = asRow<NetworkServer>(row, ['id', 'server_id']);
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
                    {isProvider && (
                      <DropdownMenuItem
                        onSelect={() => {
                          const memberIp = page.props.memberIps.find((m) => m.id === member.id);
                          if (memberIp) {
                            dialog.networkEditServer.open({ networkId: network.id, member: memberIp });
                          }
                        }}
                      >
                        Edit IP
                      </DropdownMenuItem>
                    )}
                    {!isProvider && (
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
