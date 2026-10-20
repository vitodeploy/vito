import { Head, Link, usePage, usePoll } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { EyeIcon, PlusIcon } from 'lucide-react';
import { VitoTable } from '@/components/vito-table';
import Layout from '@/layouts/app/layout';
import { InertiaTableData } from '@forjedio/inertia-table-react';
import { useDialog } from '@/hooks/use-dialog';
import { SourceControl } from '@/types/source-control';
import DocsLink from './components/docs-link';

export default function Applications() {
  const { applications, sourceControls, canCreate } = usePage<{
    applications: InertiaTableData;
    sourceControls: SourceControl[];
    canCreate: boolean;
  }>().props;
  const dialog = useDialog();
  usePoll(10000, { only: ['applications', 'canCreate'] });

  return (
    <Layout>
      <Head title="Applications" />
      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Applications" description="Repositories and environments in this project" badges={<Badge variant="warning">Beta</Badge>} />
          {canCreate && <Button onClick={() => dialog.createApplication.open({ sourceControls })}><PlusIcon />Create application</Button>}
        </HeaderContainer>
        <VitoTable tableData={applications} actions={(row) => (
          <div className="flex justify-end">
            <Button variant="outline" size="sm" asChild>
              <Link href={`/applications/${row.id}`} aria-label={`View ${row.name}`} prefetch><EyeIcon /></Link>
            </Button>
          </div>
        )} />
        {applications.data.length === 0 && <DocsLink />}
      </Container>
    </Layout>
  );
}
