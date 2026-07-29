import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import { FirewallRule } from '@/types/firewall';
import ServerLayout from '@/layouts/server/layout';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, MoreVerticalIcon, PlusIcon } from 'lucide-react';
import Container from '@/components/container';
import { VitoTable } from '@/components/vito-table';
import Delete from '@/pages/firewall/components/delete';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import type { CellRenderProps, InertiaTableData, Row } from '@forjedio/inertia-table-react';
import { asRow } from '@/lib/inertia-table';
import { useDialog } from '@/hooks/use-dialog';

const uppercaseCell = ({ value }: CellRenderProps) => <span className="uppercase">{value == null || value === '' ? '-' : String(value)}</span>;

export default function Firewall() {
  const page = usePage<{
    server: Server;
    rules: InertiaTableData;
    networkRules: InertiaTableData;
  }>();
  const dialog = useDialog();
  const hasNetworkRules = (page.props.networkRules?.data?.length ?? 0) > 0;

  return (
    <ServerLayout>
      <Head title={`Firewall - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Firewall" description="Here you can manage server's firewall rules" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/firewall" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <Button onClick={() => dialog.firewallForm.open({ serverId: page.props.server.id })}>
              <PlusIcon />
              <span className="hidden lg:block">Create</span>
            </Button>
          </div>
        </HeaderContainer>

        <VitoTable
          tableData={page.props.rules}
          cellRenderers={{ type: uppercaseCell, protocol: uppercaseCell }}
          actions={(row: Row) => {
            const firewallRule = asRow<FirewallRule>(row, ['id', 'name', 'server_id']);
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
                    <DropdownMenuItem onSelect={() => dialog.firewallForm.open({ serverId: firewallRule.server_id, firewallRule })}>
                      Edit
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <Delete firewallRule={firewallRule} />
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            );
          }}
        />

        {hasNetworkRules && (
          <div className="flex flex-col gap-4">
            <div>
              <h2 className="text-base font-medium">Private network rules</h2>
              <p className="text-muted-foreground text-sm">
                These rules are applied by the private networks this server belongs to. They are read-only here &mdash; edit them from the network.
              </p>
            </div>
            <VitoTable tableData={page.props.networkRules} />
          </div>
        )}
      </Container>
    </ServerLayout>
  );
}
