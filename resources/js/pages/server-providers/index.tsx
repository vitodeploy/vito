import SettingsLayout from '@/layouts/settings/layout';
import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import ConnectServerProvider from '@/pages/server-providers/components/connect-server-provider';
import { VitoTable } from '@/components/vito-table';
import { ServerProvider } from '@/types/server-provider';
import { BookOpenIcon, MoreVerticalIcon } from 'lucide-react';
import { DropdownMenu, DropdownMenuContent, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import Edit from '@/pages/server-providers/components/edit';
import Delete from '@/pages/server-providers/components/delete';
import type { InertiaTableData, Row } from '@forjedio/inertia-table-react';
import { asRow } from '@/lib/inertia-table';

type Page = {
  serverProviders: InertiaTableData;
  configs: {
    server_providers: string[];
  };
};

export default function ServerProviders() {
  const page = usePage<Page>();

  return (
    <SettingsLayout>
      <Head title="Server Providers" />
      <Container className="max-w-5xl">
        <div className="flex items-start justify-between">
          <Heading title="Server Providers" description="Here you can manage all of the server provider connections" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/settings/server-providers" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <ConnectServerProvider>
              <Button>Connect</Button>
            </ConnectServerProvider>
          </div>
        </div>

        <VitoTable
          tableData={page.props.serverProviders}
          actions={(row: Row) => {
            const serverProvider = asRow<ServerProvider>(row, ['id', 'name', 'global']);
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
                    <Edit serverProvider={serverProvider} />
                    <DropdownMenuSeparator />
                    <Delete serverProvider={serverProvider} />
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            );
          }}
        />
      </Container>
    </SettingsLayout>
  );
}
