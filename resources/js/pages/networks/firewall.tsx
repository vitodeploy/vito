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
import { Network, NetworkFirewallRule } from '@/types/network';

export default function NetworkFirewall() {
  const page = usePage<{
    network: Network;
    rules: InertiaTableData;
  }>();
  const dialog = useDialog();
  const network = useRealtimeRecord<Network>(page.props.network, 'network')!;

  return (
    <NetworkLayout>
      <Head title={`Firewall - ${network.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading
            title="Firewall"
            description="Rules allow traffic from the network. The default 'Allow all' rule permits everything - delete it to lock the network down"
          />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/networks/firewall" target="_blank" rel="noreferrer">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <Button onClick={() => dialog.networkFirewallForm.open({ networkId: network.id })}>
              <PlusIcon />
              <span className="hidden lg:block">Rule</span>
            </Button>
          </div>
        </HeaderContainer>

        <VitoTable
          tableData={page.props.rules}
          actions={(row: Row) => {
            const rule = asRow<NetworkFirewallRule>(row, ['id', 'name']);
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
      </Container>
    </NetworkLayout>
  );
}
