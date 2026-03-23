import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import ServerLayout from '@/layouts/server/layout';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, MoreVerticalIcon, PlusIcon } from 'lucide-react';
import Container from '@/components/container';
import { Service } from '@/types/service';
import InstallService from '@/pages/services/components/install';
import { DynamicTable } from '@/components/dynamic-table';
import { DynamicTableData } from '@/types/dynamic-table';
import { DropdownMenu, DropdownMenuContent, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import Extensions from '@/pages/php/components/extensions';
import PHPIni from '@/pages/php/components/ini';
import DefaultCli from '@/pages/php/components/default-cli';
import { Action } from '@/pages/services/components/action';
import Uninstall from '@/pages/services/components/uninstall';

export default function PHP() {
  const page = usePage<{
    server: Server;
    installedVersions: DynamicTableData;
  }>();

  return (
    <ServerLayout>
      <Head title={`PHP - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="PHP" description="Here you can manage PHP" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/php" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <InstallService name="php">
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">Install</span>
              </Button>
            </InstallService>
          </div>
        </HeaderContainer>

        <DynamicTable
          tableData={page.props.installedVersions}
          realtimeEvent="service"
          actions={(row) => {
            const service = row as unknown as Service;
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
                    <Extensions service={service} />
                    <PHPIni service={service} type="fpm" />
                    <PHPIni service={service} type="cli" />
                    <DefaultCli service={service} />
                    <DropdownMenuSeparator />
                    <Action type="start" service={service} />
                    <Action type="stop" service={service} />
                    <Action type="restart" service={service} />
                    <Action type="reload" service={service} />
                    <Action type="enable" service={service} />
                    <Action type="disable" service={service} />
                    <DropdownMenuSeparator />
                    <Uninstall service={service} />
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
