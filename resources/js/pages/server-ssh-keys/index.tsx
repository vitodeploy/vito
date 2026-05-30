import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { VitoTable } from '@/components/vito-table';
import { SshKey } from '@/types/ssh-key';
import DeployKey from '@/pages/server-ssh-keys/components/deploy-key';
import Delete from '@/pages/server-ssh-keys/components/delete';
import ServerLayout from '@/layouts/server/layout';
import HeaderContainer from '@/components/header-container';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { BookOpenIcon, MoreVerticalIcon, RocketIcon } from 'lucide-react';
import type { InertiaTableData, Row } from '@forjedio/inertia-table-react';
import { asRow } from '@/lib/inertia-table';

type Page = {
  sshKeys: InertiaTableData;
};

export default function SshKeys() {
  const page = usePage<Page>();

  return (
    <ServerLayout>
      <Head title="SSH Keys" />
      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="SSH Keys" description="Here you can manage the ssh keys deployed to the server" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/ssh-keys" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <DeployKey>
              <Button>
                <RocketIcon />
                Deploy key
              </Button>
            </DeployKey>
          </div>
        </HeaderContainer>

        <VitoTable
          tableData={page.props.sshKeys}
          actions={(row: Row) => (
            <div className="flex items-center justify-end">
              <DropdownMenu modal={false}>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" className="h-8 w-8 p-0">
                    <span className="sr-only">Open menu</span>
                    <MoreVerticalIcon />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <Delete sshKey={asRow<SshKey>(row, ['id', 'name'])} />
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          )}
        />
      </Container>
    </ServerLayout>
  );
}
