import { Head, Link, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { PencilIcon, Trash2Icon } from 'lucide-react';
import EnvironmentLayout from '@/layouts/environment/layout';
import { useDialog } from '@/hooks/use-dialog';
import { Application, Environment, PrimaryCandidate } from '@/types/application';

export default function ShowEnvironment() {
  const { application, environment, primaryCandidates, can } = usePage<{
    application: Application;
    environment: Environment;
    primaryCandidates: PrimaryCandidate[];
    can: { update: boolean; delete: boolean };
  }>().props;
  const dialog = useDialog();
  const ready = application.status === 'ready' && environment.status === 'ready';
  const baseUrl = `/applications/${application.id}/environments/${environment.id}`;
  const primary = primaryCandidates.find((candidate) => candidate.id === environment.primary_component_id);

  return (
    <EnvironmentLayout>
      <Head title={`${environment.name} - ${application.name}`} />
      <Container className="max-w-5xl">
        <HeaderContainer>
          <div className="flex items-center gap-3">
            <Heading title={environment.name} description={application.name} />
            <Badge variant="warning">Beta</Badge>
            <Badge variant={environment.status_color}>{environment.status}</Badge>
          </div>
          <div className="flex items-center gap-2">
            {can.update && ready && <Button variant="outline" onClick={() => dialog.editEnvironment.open({ application, environment, primaryCandidates })}><PencilIcon />Edit</Button>}
            {can.delete && application.status === 'ready' && environment.status !== 'deleting' && <Button variant="destructive" onClick={() => dialog.deleteApplicationResource.open({ application, environment })}><Trash2Icon />{environment.status === 'deletion_failed' ? 'Retry deletion' : 'Delete'}</Button>}
          </div>
        </HeaderContainer>
        {environment.last_error && <p role="alert" className="text-destructive text-sm">{environment.last_error}</p>}
        <Card>
          <CardContent className="p-4">
            <dl className="grid gap-4 sm:grid-cols-3">
              <div><dt className="text-muted-foreground text-sm">Slug</dt><dd>{environment.slug}</dd></div>
              <div><dt className="text-muted-foreground text-sm">Branch</dt><dd>{environment.branch}</dd></div>
              <div><dt className="text-muted-foreground text-sm">Runtime version</dt><dd>{environment.runtime_version ?? 'Not required'}</dd></div>
              <div><dt className="text-muted-foreground text-sm">Primary component</dt><dd>{primary ? <Link className="hover:underline" href={`${baseUrl}/components/${primary.id}`}>{primary.name}</Link> : 'None selected'}</dd></div>
              <div><dt className="text-muted-foreground text-sm">Components</dt><dd><Link className="hover:underline" href={`${baseUrl}/components`}>{environment.components_count ?? 0}</Link></dd></div>
            </dl>
          </CardContent>
        </Card>
        <p className="text-muted-foreground text-sm">Deployment controls will be available in a future update.</p>
      </Container>
    </EnvironmentLayout>
  );
}
