import { Head, usePage } from '@inertiajs/react';
import { PaginatedData } from '@/types';
import Layout from '@/layouts/app/layout';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, PlusIcon } from 'lucide-react';
import Container from '@/components/container';
import { DataTable } from '@/components/data-table';
import { Script } from '@/types/script';
import { columns } from '@/pages/scripts/components/columns';
import { useDialog } from '@/hooks/use-dialog';

export default function Scripts() {
  const page = usePage<{
    scripts: PaginatedData<Script>;
  }>();
  const dialog = useDialog();

  return (
    <Layout>
      <Head title="Scripts" />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Scripts" description="These are the scripts that you can run on your site's location" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/scripts" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <Button onClick={() => dialog.scriptForm.open({})}>
              <PlusIcon />
              <span className="hidden lg:block">Create</span>
            </Button>
          </div>
        </HeaderContainer>

        <DataTable columns={columns} paginatedData={page.props.scripts} />
      </Container>
    </Layout>
  );
}
