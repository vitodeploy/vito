import { Head, Link, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, EyeIcon, PlusIcon, RefreshCwIcon } from 'lucide-react';
import { VitoTable } from '@/components/vito-table';
import Layout from '@/layouts/app/layout';
import type { InertiaTableData, Row } from '@forjedio/inertia-table-react';
import { useDialog } from '@/hooks/use-dialog';
import { NetworkServerOption } from '@/types/network';

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
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/networks/overview" target="_blank" rel="noreferrer">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <Button
              variant="outline"
              onClick={() =>
                dialog.confirm.open({
                  title: 'Sync provider networks',
                  description:
                    "Query every cloud provider connection used by this project's servers and create, update or remove provider-managed networks to match. Servers Vito does not manage are ignored.",
                  confirmLabel: 'Sync',
                  method: 'post',
                  url: route('networks.sync-providers'),
                })
              }
            >
              <RefreshCwIcon />
              <span className="hidden lg:block">Sync</span>
            </Button>
            <Button onClick={() => dialog.networkCreate.open({ servers: page.props.servers })}>
              <PlusIcon />
              <span className="hidden lg:block">Create</span>
            </Button>
          </div>
        </HeaderContainer>

        <VitoTable
          tableData={page.props.networks}
          actions={(row: Row) => (
            <div className="flex items-center justify-end">
              <Button variant="outline" size="sm" asChild>
                <Link href={route('networks.show', { network: row.id })} aria-label="View network" prefetch>
                  <EyeIcon />
                </Link>
              </Button>
            </div>
          )}
        />
      </Container>
    </Layout>
  );
}
