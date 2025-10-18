import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, PlusIcon } from 'lucide-react';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/scripts/components/columns';
import { PaginatedData } from '@/types';
import { Script } from '@/types/script';
import { Site } from '@/types/site';
import Layout from '@/layouts/app/layout';
import ScriptForm from '@/pages/scripts/components/form';

export default function Scripts() {
  const page = usePage<{
    server: Server;
    site: Site;
    scripts: PaginatedData<Script>;
  }>();

  return (
    <Layout>
      <Head title={`Scripts`} />

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
            <ScriptForm>
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">Create</span>
              </Button>
            </ScriptForm>
          </div>
        </HeaderContainer>

        <DataTable columns={columns} paginatedData={page.props.scripts} />
      </Container>
    </Layout>
  );
}
