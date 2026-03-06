import ServerLayout from '@/layouts/server/layout';
import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import { PaginatedData } from '@/types';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, MoreHorizontalIcon, PlusIcon } from 'lucide-react';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/ssls/components/columns';
import { SSL } from '@/types/ssl';
import CreateSSL from '@/pages/ssls/components/create-ssl';
import { Site } from '@/types/site';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import React from 'react';
import ToggleForceSSL from '@/pages/ssls/components/force-ssl';
import { useRealtime } from '@/hooks/use-socket-events';

export default function Ssls() {
  const page = usePage<{
    server: Server;
    site: Site;
    ssls: PaginatedData<SSL>;
  }>();

  const [ssls] = useRealtime<SSL>(page.props.ssls, 'ssl');

  return (
    <ServerLayout>
      <Head title={`SSL - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="SSL" description="Here you can SSL certificates" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/sites/ssl" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <CreateSSL site={page.props.site}>
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">New SSL</span>
              </Button>
            </CreateSSL>
            <DropdownMenu modal={false}>
              <DropdownMenuTrigger asChild>
                <Button variant="outline">
                  <span className="sr-only">Open menu</span>
                  <MoreHorizontalIcon />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <ToggleForceSSL site={page.props.site} />
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </HeaderContainer>

        <DataTable columns={columns} paginatedData={ssls} />
      </Container>
    </ServerLayout>
  );
}
