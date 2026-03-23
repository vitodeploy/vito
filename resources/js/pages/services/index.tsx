import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import ServerLayout from '@/layouts/server/layout';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, MoreVerticalIcon, PlusIcon } from 'lucide-react';
import Container from '@/components/container';
import { DynamicTable } from '@/components/dynamic-table';
import { DynamicTableData } from '@/types/dynamic-table';
import { Service } from '@/types/service';
import InstallService from '@/pages/services/components/install';
import { DropdownMenu, DropdownMenuContent, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Action } from '@/pages/services/components/action';
import ConfigFile from '@/pages/services/components/config-file';
import InstallationLog from '@/pages/services/components/installation-log';
import Uninstall from '@/pages/services/components/uninstall';

export default function WorkerIndex() {
  const page = usePage<{
    server: Server;
    services: DynamicTableData;
  }>();

  return (
    <ServerLayout>
      <Head title={`Services - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Services" description="Here you can manage server's services" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/services" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <InstallService>
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">Install</span>
              </Button>
            </InstallService>
          </div>
        </HeaderContainer>

        <DynamicTable
          tableData={page.props.services}
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
                    <Action type="start" service={service} />
                    <Action type="stop" service={service} />
                    <Action type="restart" service={service} />
                    <Action type="reload" service={service} />
                    <Action type="enable" service={service} />
                    <Action type="disable" service={service} />
                    {service.config_paths && service.config_paths.length > 0 && (
                      <>
                        <DropdownMenuSeparator />
                        {service.config_paths.map((configPath) => (
                          <ConfigFile key={configPath.name} service={service} configPath={configPath} />
                        ))}
                      </>
                    )}
                    {service.log && (
                      <>
                        <DropdownMenuSeparator />
                        <InstallationLog service={service} />
                      </>
                    )}
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
