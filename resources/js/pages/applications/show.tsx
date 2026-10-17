import { Head, Link, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { ArrowLeftIcon, PlusIcon, Trash2Icon } from 'lucide-react';
import Layout from '@/layouts/app/layout';
import { useDialog } from '@/hooks/use-dialog';
import { useConfigs } from '@/stores/bootstrap-store';
import { Application, Environment } from '@/types/application';
import { SourceControl } from '@/types/source-control';
import Settings from './components/settings';

export default function ShowApplication() {
  const { application, environments, sourceControls, can } = usePage<{
    application: Application;
    environments: Environment[];
    sourceControls: SourceControl[];
    can: { update: boolean; delete: boolean; createEnvironment: boolean };
  }>().props;
  const dialog = useDialog();
  const runtime = useConfigs()?.application.runtimes.find((preset) => preset.id === application.runtime);

  return (
    <Layout>
      <Head title={application.name} />
      <Container className="max-w-5xl">
        <div><Button variant="ghost" size="sm" asChild><Link href="/applications"><ArrowLeftIcon />Applications</Link></Button></div>
        <HeaderContainer>
          <div className="flex items-center gap-3">
            <Heading title={application.name} description={application.repository} />
            <Badge variant="warning">Beta</Badge>
            <Badge variant="outline">{runtime?.label ?? application.runtime}</Badge>
          </div>
          {can.delete && <Button variant="destructive" onClick={() => dialog.deleteApplicationResource.open({ application })}><Trash2Icon />Delete</Button>}
        </HeaderContainer>
        <section className="flex flex-col gap-4" aria-label="Environments">
          <HeaderContainer>
            <Heading title="Environments" />
            {can.createEnvironment && <Button onClick={() => dialog.createEnvironment.open({ application })}><PlusIcon />Create environment</Button>}
          </HeaderContainer>
          {environments.length === 0 && <p className="text-muted-foreground text-sm">No environments yet. Create one to get started.</p>}
          <div className="grid gap-4 sm:grid-cols-2">
            {environments.map((environment) => (
              <Card key={environment.id}>
                <CardContent className="flex flex-col gap-3 p-4">
                  <div className="flex items-center justify-between gap-2">
                    <Link className="font-medium hover:underline" href={`/applications/${application.id}/environments/${environment.id}`} prefetch>{environment.name}</Link>
                    <Badge variant={environment.status_color}>{environment.status}</Badge>
                  </div>
                  <p className="text-muted-foreground text-sm">Branch: {environment.branch}</p>
                  <p className="text-muted-foreground text-sm">Runtime version: {environment.runtime_version ?? 'Not required'}</p>
                </CardContent>
              </Card>
            ))}
          </div>
        </section>
        {can.update && <Settings key={application.id} application={application} sourceControls={sourceControls} />}
      </Container>
    </Layout>
  );
}
