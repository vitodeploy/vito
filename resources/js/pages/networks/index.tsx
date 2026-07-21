import { Head, Link, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { EyeIcon, PlusIcon } from 'lucide-react';
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
          <Button onClick={() => dialog.networkCreate.open({ servers: page.props.servers })}>
            <PlusIcon />
            <span className="hidden lg:block">Create</span>
          </Button>
        </HeaderContainer>

        <VitoTable
          tableData={page.props.networks}
          actions={(row: Row) => (
            <div className="flex items-center justify-end">
              <Link href={route('networks.show', { network: row.id })} prefetch>
                <Button variant="outline" size="sm">
                  <EyeIcon />
                </Button>
              </Link>
            </div>
          )}
        />
      </Container>
    </Layout>
  );
}
