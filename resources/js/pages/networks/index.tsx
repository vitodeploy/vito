import { Head, router, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { MoreVerticalIcon, PlusIcon } from 'lucide-react';
import { VitoTable } from '@/components/vito-table';
import Layout from '@/layouts/app/layout';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import type { InertiaTableData, Row } from '@forjedio/inertia-table-react';
import { asRow } from '@/lib/inertia-table';
import { useDialog } from '@/hooks/use-dialog';
import { Network, NetworkServerOption } from '@/types/network';

export default function Networks() {
  const page = usePage<{
    networks: InertiaTableData;
    servers: NetworkServerOption[];
  }>();
  const dialog = useDialog();

  return (
    <Layout>
      <Head title="Networks" />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Networks" description="Private networks connecting the servers in this project" />
          <Button onClick={() => dialog.networkCreate.open({ servers: page.props.servers })}>
            <PlusIcon />
            <span className="hidden lg:block">Create</span>
          </Button>
        </HeaderContainer>

        <VitoTable
          tableData={page.props.networks}
          actions={(row: Row) => {
            const network = asRow<Network>(row, ['id', 'name']);
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
                    <DropdownMenuItem onSelect={() => router.visit(route('networks.show', { network: network.id }))}>View</DropdownMenuItem>
                    <DropdownMenuItem
                      onSelect={() =>
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
                    </DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                      variant="destructive"
                      onSelect={() =>
                        dialog.confirm.open({
                          title: `Delete network [${network.name}]`,
                          description: `Are you sure you want to delete ${network.name}? This tears the network down on all of its servers.`,
                          variant: 'destructive',
                          confirmLabel: 'Delete',
                          method: 'delete',
                          url: route('networks.destroy', { network: network.id }),
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
    </Layout>
  );
}
