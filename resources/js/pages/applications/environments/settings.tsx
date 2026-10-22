import { Head, usePage } from '@inertiajs/react';
import { InfoIcon, LockIcon, PencilIcon } from 'lucide-react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useDialog } from '@/hooks/use-dialog';
import ApplicationLayout from '@/layouts/application/layout';
import ApplicationSettingsForm from '@/pages/applications/components/application-settings-form';
import EnvironmentSettingsForm from '@/pages/applications/components/environment-settings-form';
import ReleaseSettingsForm from '@/pages/applications/components/release-settings-form';
import { Row, Section } from '@/pages/applications/components/section';
import { stages } from '@/pages/applications/components/stages';
import { useConfigs } from '@/stores/bootstrap-store';
import { Application, Environment, PrimaryCandidate } from '@/types/application';
import { DeploymentScript } from '@/types/deployment-script';
import { SourceControl } from '@/types/source-control';

export default function Settings() {
  const { application, environment, scripts, primaryCandidates, sourceControls, deploymentActive, can } = usePage<{
    application: Application;
    environment: Environment;
    scripts: DeploymentScript[];
    primaryCandidates: PrimaryCandidate[];
    sourceControls: SourceControl[];
    deploymentActive: boolean;
    can: { update: boolean; delete: boolean; updateApplication: boolean; deleteApplication: boolean };
  }>().props;
  const dialog = useDialog();
  const runtime = useConfigs()?.application.runtimes.find((preset) => preset.id === application.runtime)?.label ?? application.runtime;
  const ready = application.status === 'ready' && environment.status === 'ready';
  const editable = can.update && ready;
  const deployEditable = editable && !deploymentActive;
  const scriptUrl = `/applications/${application.id}/environments/${environment.id}/deployment-scripts`;

  return (
    <ApplicationLayout>
      <Head title={`Settings · ${environment.name}`} />
      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Settings" description={`Configure ${environment.name} and the ${application.name} application.`} />
        </HeaderContainer>
        {(!ready || !can.update) && (
          <Alert>
            <InfoIcon />
            <AlertDescription>{!ready ? 'Settings are read-only until the environment is ready.' : 'You have read-only access to this environment.'}</AlertDescription>
          </Alert>
        )}

        <EnvironmentSettingsForm key={`environment-${environment.id}`} application={application} environment={environment} primaryCandidates={primaryCandidates} disabled={!editable} />

        <Section
          title="Deploy scripts"
          description={<>Plain shell, run in the release directory with your variables plus <code>COMMIT_ID</code>, <code>RELEASE_PATH</code> and <code>BRANCH</code>.</>}
          action={deploymentActive && <Badge variant="outline"><LockIcon />Locked while deploying</Badge>}
        >
          {stages.map((stage) => {
            const script = scripts.find((item) => item.name === stage.id);
            const lines = (script?.content ?? '').split('\n').filter((line) => line.trim() !== '');
            return (
              <Row key={stage.id} label={stage.label} description={stage.description}>
                <code className="max-w-56 truncate font-mono text-xs" title={script?.content}>
                  {lines.length > 0 ? lines[0] : 'Skipped'}{lines.length > 1 && ` +${lines.length - 1}`}
                </code>
                {deployEditable && (
                  <Button variant="outline" size="sm" onClick={() => dialog.environmentDeploymentScript.open({ url: scriptUrl, stage: stage.id, label: stage.label, script, componentOverride: false })}>
                    <PencilIcon />Edit
                  </Button>
                )}
              </Row>
            );
          })}
        </Section>

        <ReleaseSettingsForm key={`releases-${environment.id}`} application={application} environment={environment} disabled={!deployEditable} />

        <ApplicationSettingsForm key={`application-${application.id}`} application={application} runtime={runtime} sourceControls={sourceControls} disabled={!can.updateApplication || application.status !== 'ready'} />

        {(can.delete || can.deleteApplication) && (
          <Section destructive title="Danger zone" description="Deleting removes components from their servers. This cannot be undone.">
            {can.delete && environment.status !== 'deleting' && (
              <Row label={`Delete ${environment.name}`} description="Other environments are not affected.">
                <Button variant="destructive" size="sm" disabled={application.status !== 'ready'} onClick={() => dialog.deleteApplicationResource.open({ application, environment })}>
                  {environment.status === 'deletion_failed' ? 'Retry' : 'Delete'}
                </Button>
              </Row>
            )}
            {can.deleteApplication && application.status !== 'deleting' && (
              <Row label={`Delete ${application.name}`} description="Deletes every environment of this application.">
                <Button variant="destructive" size="sm" onClick={() => dialog.deleteApplicationResource.open({ application })}>
                  {application.status === 'deletion_failed' ? 'Retry' : 'Delete'}
                </Button>
              </Row>
            )}
          </Section>
        )}
      </Container>
    </ApplicationLayout>
  );
}
