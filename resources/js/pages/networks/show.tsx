import { Head, router, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { MoreVerticalIcon, PlusIcon } from 'lucide-react';
import { VitoTable } from '@/components/vito-table';
import Layout from '@/layouts/app/layout';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import type { InertiaTableData, Row } from '@forjedio/inertia-table-react';
import { asRow } from '@/lib/inertia-table';
import { useDialog } from '@/hooks/use-dialog';
import { Network, NetworkFirewallRule, NetworkServer, NetworkServerOption } from '@/types/network';

export default function ShowNetwork() {
  const page = usePage<{
    network: { data: Network };
    members: InertiaTableData;
    rules: InertiaTableData;
    servers: NetworkServerOption[];
  }>();
  const dialog = useDialog();
  const network = page.props.network.data;
  const isProvider = network.type_value === 'provider';

  const toggleFirewall = () => {
    router.put(route('networks.update', { network: network.id }), { firewall_enabled: !network.firewall_enabled }, { preserveScroll: true });
  };

  return (
    <Layout>
      <Head title={`Network - ${network.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading
            title={network.name}
            description={`${network.type} · ${network.status}${network.cidr ? ` · ${network.cidr}` : ''}`}
          />
          <div className="flex items-center gap-2">
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
            <Button
              variant="destructive"
              onClick={() =>
                dialog.confirm.open({
                  title: `Delete network [${network.name}]`,
                  description: `Are you sure you want to delete ${network.name}?`,
                  variant: 'destructive',
                  confirmLabel: 'Delete',
                  method: 'delete',
                  url: route('networks.destroy', { network: network.id }),
                })
              }
            >
              Delete
            </Button>
          </div>
        </HeaderContainer>

        <div className="flex flex-col gap-6">
          <div className="rounded-xl border">
            <div className="flex items-center justify-between border-b p-4">
              <h2 className="font-medium">Servers</h2>
              <Button size="sm" onClick={() => dialog.networkAddServer.open({ networkId: network.id, isProvider, servers: page.props.servers })}>
                <PlusIcon />
                <span className="hidden lg:block">Add server</span>
              </Button>
            </div>
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
          </div>

          <div className="rounded-xl border">
            <div className="flex flex-col gap-3 border-b p-4 sm:flex-row sm:items-center sm:justify-between">
              <div>
                <h2 className="font-medium">Firewall</h2>
                <p className="text-muted-foreground text-sm">Traffic from the network is allowed by default. Add deny rules to restrict it.</p>
              </div>
              <div className="flex items-center gap-4">
                <div className="flex items-center gap-2">
                  <Checkbox id="firewall_enabled" checked={network.firewall_enabled} onClick={toggleFirewall} />
                  <Label htmlFor="firewall_enabled">Enabled</Label>
                </div>
                <Button
                  size="sm"
                  disabled={!network.firewall_enabled}
                  onClick={() => dialog.networkFirewallForm.open({ networkId: network.id })}
                >
                  <PlusIcon />
                  <span className="hidden lg:block">Rule</span>
                </Button>
              </div>
            </div>
            <VitoTable
              tableData={page.props.rules}
              actions={(row: Row) => {
                const rule = asRow<NetworkFirewallRule>(row, ['id', 'name', 'type', 'protocol', 'port', 'position']);
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
                        <DropdownMenuItem onSelect={() => dialog.networkFirewallForm.open({ networkId: network.id, rule })}>Edit</DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                          variant="destructive"
                          onSelect={() =>
                            dialog.confirm.open({
                              title: `Delete rule [${rule.name}]`,
                              description: 'Are you sure you want to delete this firewall rule?',
                              variant: 'destructive',
                              confirmLabel: 'Delete',
                              method: 'delete',
                              url: route('networks.firewall.destroy', { network: network.id, networkFirewallRule: rule.id }),
                            })
                          }
                        >
                          Delete
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </div>
                );
              }}
            />
          </div>
        </div>
      </Container>
    </Layout>
  );
}
