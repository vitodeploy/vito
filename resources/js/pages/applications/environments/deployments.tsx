import { Head, Link, usePage } from '@inertiajs/react';
import { EyeIcon, SettingsIcon } from 'lucide-react';
import { InertiaTableData } from '@forjedio/inertia-table-react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import DocsLink from '@/pages/applications/components/docs-link';
import { Button } from '@/components/ui/button';
import { VitoTable } from '@/components/vito-table';
import EnvironmentLayout from '@/layouts/environment/layout';
import DeployButton from '@/pages/applications/components/deploy-button';
import { Application, Environment } from '@/types/application';

export default function Deployments() {
  const { application, environment, deployments } = usePage<{
    application: Application;
    environment: Environment;
    deployments: InertiaTableData;
  }>().props;
  const baseUrl = `/applications/${application.id}/environments/${environment.id}`;

  return (
    <EnvironmentLayout>
      <Head title={`Deployments - ${environment.name}`} />
      <Container className="max-w-5xl">
        <HeaderContainer>
          <div className="flex items-center gap-3"><Heading title="Deployments" description="Each run deploys a pinned commit to every ready app component. Only the latest queued run is kept." /><Badge variant="warning">Beta</Badge></div>
          <div className="flex items-start gap-2">
            <Button variant="outline" asChild><Link href={`${baseUrl}/deployment-settings`}><SettingsIcon />Settings</Link></Button>
            <DeployButton application={application} environment={environment} />
          </div>
        </HeaderContainer>
        <VitoTable tableData={deployments} actions={(row) => (
          <div className="flex justify-end">
            <Button variant="outline" size="sm" asChild>
              <Link href={`${baseUrl}/deployments/${row.id}`} aria-label={`View deployment ${row.release}`}><EyeIcon /></Link>
            </Button>
          </div>
        )} />
        {deployments.data.length === 0 && <DocsLink page="deployments" />}
      </Container>
    </EnvironmentLayout>
  );
}
