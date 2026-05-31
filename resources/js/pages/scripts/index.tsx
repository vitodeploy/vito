import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import { PaginatedData } from '@/types';
import ServerLayout from '@/layouts/server/layout';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { PlusIcon } from 'lucide-react';
import Container from '@/components/container';
import { DataTable } from '@/components/data-table';
import { Script } from '@/types/script';
import { columns } from '@/pages/scripts/components/columns';
import { Site } from '@/types/site';
import { useDialog } from '@/hooks/use-dialog';

export default function Scripts() {
  const page = usePage<{
    server: Server;
    scripts: PaginatedData<Script>;
    site?: Site;
  }>();
  const dialog = useDialog();

  return (
    <ServerLayout>
      <Head title={`Scripts - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Scripts" description="Here you can manage the scripts" />
          <Button onClick={() => dialog.scriptForm.open({})}>
            <PlusIcon />
            <span className="hidden lg:block">Create</span>
          </Button>
        </HeaderContainer>

        <DataTable columns={columns} paginatedData={page.props.scripts} />
      </Container>
    </ServerLayout>
  );
}
