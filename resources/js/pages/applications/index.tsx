import { Head, Link, usePage } from '@inertiajs/react';
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

export default function Applications() {
  const { applications, sourceControls, canCreate } = usePage<{
    applications: InertiaTableData;
    sourceControls: SourceControl[];
    canCreate: boolean;
  }>().props;
  const dialog = useDialog();

  return (
    <Layout>
      <Head title="Applications" />
      <Container className="max-w-5xl">
        <HeaderContainer>
          <div className="flex items-center gap-3">
            <Heading title="Applications" description="Repositories and environments in this project" />
            <Badge variant="warning">Beta</Badge>
          </div>
          {canCreate && <Button onClick={() => dialog.createApplication.open({ sourceControls })}><PlusIcon />Create application</Button>}
        </HeaderContainer>
        <VitoTable tableData={applications} actions={(row) => (
          <div className="flex justify-end">
            <Button variant="outline" size="sm" asChild>
              <Link href={`/applications/${row.id}`} aria-label={`View ${row.name}`} prefetch><EyeIcon /></Link>
            </Button>
          </div>
        )} />
      </Container>
    </Layout>
  );
}
