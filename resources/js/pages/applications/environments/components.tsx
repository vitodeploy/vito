import { Head, Link, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import DocsLink from '@/pages/applications/components/docs-link';
import { EyeIcon, PlusIcon } from 'lucide-react';
import { VitoTable } from '@/components/vito-table';
import EnvironmentLayout from '@/layouts/environment/layout';
import { InertiaTableData } from '@forjedio/inertia-table-react';
import { useDialog } from '@/hooks/use-dialog';
import { Application, ComponentOptions, Environment } from '@/types/application';

export default function Components() {
  const { application, environment, components, options, canCreate } = usePage<{
    application: Application;
    environment: Environment;
    components: InertiaTableData;
    options: ComponentOptions;
    canCreate: boolean;
  }>().props;
  const dialog = useDialog();
  const baseUrl = `/applications/${application.id}/environments/${environment.id}/components`;

  return (
    <EnvironmentLayout>
      <Head title={`Components - ${environment.name}`} />
      <Container className="max-w-5xl">
        <HeaderContainer>
          <div className="flex items-center gap-3"><Heading title="Components" description={`Server placements for ${environment.name}`} /><Badge variant="warning">Beta</Badge></div>
          {canCreate && application.status === 'ready' && environment.status === 'ready' && <Button onClick={() => dialog.addComponent.open({ application, environment, options })}><PlusIcon />Add component</Button>}
        </HeaderContainer>
        {environment.last_error && <p role="alert" className="text-destructive text-sm">{environment.last_error}</p>}
        <VitoTable tableData={components} actions={(row) => (
          <div className="flex justify-end">
            <Button variant="outline" size="sm" asChild>
              <Link href={`${baseUrl}/${row.id}`} aria-label={`View ${row.name}`}><EyeIcon /></Link>
            </Button>
          </div>
        )} />
        {components.data.length === 0 && <DocsLink page="components" />}
      </Container>
    </EnvironmentLayout>
  );
}
