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
import RuleForm from '@/pages/firewall/components/form';
import Delete from '@/pages/firewall/components/delete';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import type { InertiaTableData, Row } from '@forjedio/inertia-table-react';
import { asRow } from '@/lib/inertia-table';

export default function Firewall() {
  const page = usePage<{
    server: Server;
    rules: InertiaTableData;
  }>();

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
            <RuleForm serverId={page.props.server.id}>
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">Create</span>
              </Button>
            </RuleForm>
          </div>
        </HeaderContainer>

        <VitoTable
          tableData={page.props.rules}
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
                    <RuleForm serverId={firewallRule.server_id} firewallRule={firewallRule}>
                      <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Edit</DropdownMenuItem>
                    </RuleForm>
                    <DropdownMenuSeparator />
                    <Delete firewallRule={firewallRule} />
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            );
          }}
        />
      </Container>
    </ServerLayout>
  );
}
