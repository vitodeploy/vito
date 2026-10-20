import { Head, Link, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import DateTime from '@/components/date-time';
import { PencilIcon, Trash2Icon } from 'lucide-react';
import EnvironmentLayout from '@/layouts/environment/layout';
import { useDialog } from '@/hooks/use-dialog';
import { Application, Environment, EnvironmentDeploymentSummary, EnvironmentTopology, PrimaryCandidate } from '@/types/application';
import DeployButton from '@/pages/applications/components/deploy-button';
import DocsLink from '@/pages/applications/components/docs-link';
import Topology from '@/pages/applications/components/topology';

export default function ShowEnvironment() {
  const { application, environment, primaryCandidates, topology, lastDeployment, warnings, can } = usePage<{
    application: Application;
    environment: Environment;
    primaryCandidates: PrimaryCandidate[];
    topology: EnvironmentTopology;
    lastDeployment: EnvironmentDeploymentSummary | null;
    warnings: string[];
    can: { update: boolean; delete: boolean };
  }>().props;
  const dialog = useDialog();
  const ready = application.status === 'ready' && environment.status === 'ready';
  const baseUrl = `/applications/${application.id}/environments/${environment.id}`;
  const primary = primaryCandidates.find((candidate) => candidate.id === environment.primary_component_id);
  const components = topology.nodes.filter((node) => ['app', 'load_balancer'].includes(node.type));
  const domains = topology.nodes.filter((node) => node.type === 'domain');

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
          <div className="flex items-start gap-2">
            <DeployButton application={application} environment={environment} />
            {can.update && ready && <Button variant="outline" onClick={() => dialog.editEnvironment.open({ application, environment, primaryCandidates })}><PencilIcon />Edit</Button>}
            {can.delete && application.status === 'ready' && environment.status !== 'deleting' && <Button variant="destructive" onClick={() => dialog.deleteApplicationResource.open({ application, environment })}><Trash2Icon />{environment.status === 'deletion_failed' ? 'Retry deletion' : 'Delete'}</Button>}
          </div>
        </HeaderContainer>
        {environment.last_error && <p role="alert" className="text-destructive text-sm">{environment.last_error}</p>}
        {warnings.length > 0 && <Alert><AlertDescription><ul className="grid list-inside list-disc gap-2">{warnings.map((warning) => <li key={warning}>{warning}</li>)}</ul></AlertDescription></Alert>}
        <Card>
          <CardContent className="p-4">
            <dl className="grid gap-4 sm:grid-cols-3">
              <div><dt className="text-muted-foreground text-sm">Slug</dt><dd>{environment.slug}</dd></div>
              <div><dt className="text-muted-foreground text-sm">Branch</dt><dd>{environment.branch}</dd></div>
              <div><dt className="text-muted-foreground text-sm">Runtime version</dt><dd>{environment.runtime_version ?? 'Not required'}</dd></div>
              <div><dt className="text-muted-foreground text-sm">Primary component</dt><dd>{primary ? <Link className="hover:underline" href={`${baseUrl}/components/${primary.id}`}>{primary.name}</Link> : 'None selected'}</dd></div>
              <div><dt className="text-muted-foreground text-sm">Components</dt><dd><Link className="hover:underline" href={`${baseUrl}/components`}>{components.length}</Link></dd></div>
              <div><dt className="text-muted-foreground text-sm">Last run</dt><dd className="flex flex-col items-start gap-2">{lastDeployment ? <>
                <Link href={`${baseUrl}/deployments/${lastDeployment.id}`} className="hover:underline">#{lastDeployment.id} · {lastDeployment.commit_id?.slice(0, 7) ?? 'Resolving commit'}</Link>
                <Badge variant={lastDeployment.status_color}>{lastDeployment.status}</Badge>
                <DateTime date={lastDeployment.created_at} />
              </> : <>No deployments yet.<DocsLink page="deployments" /></>}</dd></div>
            </dl>
          </CardContent>
        </Card>
        <section className="flex flex-col gap-4" aria-label="Topology">
          <Heading title="Topology" description="Read-only placement and routing. Status reflects Vito's lifecycle, not a live health check. Select a node to open its details." />
          {topology.nodes.length > 0 ? <Topology topology={topology} /> : <div className="flex flex-col items-start gap-2 rounded-lg border p-6"><p className="text-muted-foreground text-sm">Add a component to start building this environment.</p><Link href={`${baseUrl}/components`} className="text-sm underline">Manage components</Link><DocsLink page="components" /></div>}
        </section>
        <div className="grid gap-4 sm:grid-cols-2">
          <section className="flex flex-col gap-3" aria-label="Components">
            <Heading title="Components" />
            {components.map((node) => <div key={node.id} className="flex flex-wrap items-center gap-2 rounded-lg border p-3">
              <Link href={node.url} className="min-w-0 break-words font-medium hover:underline">{node.name}</Link><Badge variant={node.status_color}>{node.status}</Badge>
              {node.needs_redeploy && <span className="text-muted-foreground text-xs">Redeploy to apply</span>}
            </div>)}
            {components.length === 0 && <DocsLink page="components" />}
          </section>
          <section className="flex flex-col gap-3" aria-label="Domains">
            <Heading title="Domains" />
            {domains.map((node) => <div key={node.id} className="flex flex-wrap items-center gap-2 rounded-lg border p-3"><Link href={node.url} className="min-w-0 break-words font-medium hover:underline">{node.name}</Link><Badge variant={node.status_color}>{node.status}</Badge></div>)}
            {domains.length === 0 && <div className="flex flex-col items-start gap-2"><p className="text-muted-foreground text-sm">No domains yet.</p><DocsLink page="domains" /></div>}
          </section>
        </div>
        <Link href={`${baseUrl}/deployments`} className="text-sm hover:underline">View deployments</Link>
      </Container>
    </EnvironmentLayout>
  );
}
