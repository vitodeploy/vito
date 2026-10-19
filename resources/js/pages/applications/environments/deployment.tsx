import { Head, Link, usePage } from '@inertiajs/react';
import { FileTextIcon } from 'lucide-react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useDialog } from '@/hooks/use-dialog';
import { humanizeStep } from '@/lib/utils';
import EnvironmentLayout from '@/layouts/environment/layout';
import DeployButton from '@/pages/applications/components/deploy-button';
import { Application, ComponentDeployment, DeploymentStage, Environment, EnvironmentDeployment } from '@/types/application';

const stages: DeploymentStage[] = ['clone', 'build', 'Release command', 'release', 'post-release'];

function StageStatus({ run, component, stage }: { run: EnvironmentDeployment; component: ComponentDeployment; stage: DeploymentStage }) {
  if (run.rollback_of_id && stage !== 'release') {
    return <span className="text-muted-foreground">Skipped</span>;
  }
  if (stage === 'Release command' && (run.is_join || component.component_id !== run.primary_component_id)) {
    return <span className="text-muted-foreground">Skipped</span>;
  }
  const current = component.current_stage ? stages.indexOf(component.current_stage) : -1;
  const index = stages.indexOf(stage);
  if (index < current) return <Badge variant="success">Finished</Badge>;
  if (index > current) return <span className="text-muted-foreground">Not started</span>;
  if (stage === 'release' && run.is_recovering) {
    return <Badge variant={component.status_color}>{component.status === 'deploying' ? 'Restoring' : component.status === 'failed' ? 'Recovery failed' : 'Restored'}</Badge>;
  }
  return <Badge variant={component.status_color}>{humanizeStep(component.status)}</Badge>;
}

export default function Deployment() {
  const { application, environment, deployment } = usePage<{
    application: Application;
    environment: Environment;
    deployment: EnvironmentDeployment;
  }>().props;
  const dialog = useDialog();
  const baseUrl = `/applications/${application.id}/environments/${environment.id}`;

  return (
    <EnvironmentLayout>
      <Head title={`Deployment ${deployment.id} - ${environment.name}`} />
      <Container className="max-w-5xl">
        <HeaderContainer>
          <div className="flex flex-col gap-2">
            <Heading title={`Deployment #${deployment.id}`} description={deployment.release} />
            <div className="flex flex-wrap gap-2">
              <Badge variant={deployment.status_color}>{humanizeStep(deployment.status)}</Badge>
              {deployment.rollback_of_id && <Badge variant="info">Rollback of #{deployment.rollback_of_id}</Badge>}
              {deployment.is_join && <Badge variant="info">Live-release join</Badge>}
              {environment.live_run_id === deployment.id && <Badge variant="success">Live release</Badge>}
            </div>
          </div>
          <div className="flex flex-wrap gap-2">
            {environment.can_deploy && deployment.cancellable && <Button variant="outline" onClick={() => dialog.confirm.open({
              title: 'Cancel deployment', description: 'No further stages will start. In-flight work finishes before the next deployment starts.',
              confirmLabel: 'Cancel deployment', method: 'post', url: `${baseUrl}/deployments/${deployment.id}/cancel`,
            })}>Cancel deployment</Button>}
            {environment.can_deploy && deployment.status === 'finished' && !deployment.is_join && <Button variant="outline" disabled={!deployment.rollback_available}
              title={deployment.rollback_available ? 'Activate this release without changing environment variables or running scripts' : 'This release is no longer available on every deployed component'}
              onClick={() => dialog.confirm.open({
                title: `Roll back ${application.name} / ${environment.name}`, description: `Activate deployment #${deployment.id} (${deployment.release}) on all deployed components and restart their processes. Current environment variables stay unchanged. Release-command effects are not reversed. If another run is active, this replaces any pending deployment.`,
                confirmLabel: 'Roll back', method: 'post', url: `${baseUrl}/deployments/${deployment.id}/rollback`,
              })}>Roll back</Button>}
            <DeployButton application={application} environment={environment} />
          </div>
        </HeaderContainer>
        {environment.can_deploy && deployment.status === 'finished' && !deployment.is_join && !deployment.rollback_available && <p className="text-muted-foreground text-sm">Rollback is unavailable: this release is no longer available on every deployed component, or a component is not ready.</p>}
        <dl className="grid gap-4 sm:grid-cols-3">
          <div><dt className="text-muted-foreground text-sm">Commit</dt><dd className="break-all font-mono text-sm">{deployment.commit_id ?? 'Resolved when the run starts'}</dd></div>
          <div><dt className="text-muted-foreground text-sm">Branch</dt><dd>{deployment.branch}</dd></div>
          <div><dt className="text-muted-foreground text-sm">Triggered by</dt><dd>{deployment.triggered_by ?? 'Automatic'}</dd></div>
        </dl>
        {deployment.commit_data?.message && <p className="whitespace-pre-wrap break-words text-sm">{deployment.commit_data.message}</p>}
        {deployment.last_error && <Alert variant="destructive"><AlertDescription>{deployment.last_error}</AlertDescription></Alert>}
        {deployment.status === 'cancelled' && deployment.stage_deadline_at && <Alert><AlertDescription>Waiting for in-flight component work to settle before starting another deployment.</AlertDescription></Alert>}
        {deployment.is_recovering && <Alert><AlertDescription>A release failed and recovery was requested for its components. Review the recovery results below before deploying again.</AlertDescription></Alert>}
        <Table>
          <TableHeader><TableRow><TableHead>Component</TableHead>{stages.map((stage) => <TableHead key={stage}>{humanizeStep(stage)}</TableHead>)}<TableHead>Logs</TableHead></TableRow></TableHeader>
          <TableBody>
            {deployment.deployments.length === 0 && <TableRow><TableCell colSpan={7} className="text-muted-foreground text-center">{deployment.status === 'pending' || deployment.status === 'running' ? 'Waiting for the run to start.' : 'No components were deployed.'}</TableCell></TableRow>}
            {deployment.deployments.map((component) => <TableRow key={component.id}>
              <TableCell><div className="flex flex-col gap-1">
                {component.component_id ? <Link href={`${baseUrl}/components/${component.component_id}`} className="hover:underline">{component.component_name}</Link> : 'Removed component'}
                <span className="text-muted-foreground text-xs">{component.server_name}</span>
              </div></TableCell>
              {stages.map((stage) => <TableCell key={stage}><StageStatus run={deployment} component={component} stage={stage} /></TableCell>)}
              <TableCell>{component.server_id && component.log_id && <Button variant="outline" size="sm" aria-label={`View logs for ${component.component_name}`} onClick={() => dialog.logViewer.open({ serverId: component.server_id!, logId: component.log_id!, title: `${component.component_name} deployment` })}><FileTextIcon /></Button>}</TableCell>
            </TableRow>)}
          </TableBody>
        </Table>
        <Link href={`${baseUrl}/deployments`} className="text-sm hover:underline">Back to deployments</Link>
      </Container>
    </EnvironmentLayout>
  );
}
