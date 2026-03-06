import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import { PaginatedData } from '@/types';
import { FirewallRule } from '@/types/firewall';
import ServerLayout from '@/layouts/server/layout';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, PlusIcon } from 'lucide-react';
import Container from '@/components/container';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/firewall/components/columns';
import RuleForm from '@/pages/firewall/components/form';
import { useRealtime } from '@/hooks/use-socket-events';

export default function Firewall() {
  const page = usePage<{
    server: Server;
    rules: PaginatedData<FirewallRule>;
  }>();

  const [rules] = useRealtime<FirewallRule>(page.props.rules, 'firewall-rule');

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

        <DataTable columns={columns} paginatedData={rules} />
      </Container>
    </ServerLayout>
  );
}
