import { InfoIcon, LockIcon, PencilIcon, SettingsIcon } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useDialog } from '@/hooks/use-dialog';
import ApplicationSettingsForm from '@/pages/applications/components/application-settings-form';
import EnvironmentSettingsForm from '@/pages/applications/components/environment-settings-form';
import ReleaseSettingsForm from '@/pages/applications/components/release-settings-form';
import { useConfigs } from '@/stores/bootstrap-store';
import { Application, Environment, PrimaryCandidate } from '@/types/application';
import { DeploymentScript } from '@/types/deployment-script';
import { SourceControl } from '@/types/source-control';
import { Row, Section } from './section';
import SidePanel from './side-panel';
import { stages } from './stages';

export default function SettingsSheet({
  application,
  environment,
  scripts,
  primaryCandidates,
  sourceControls,
  deploymentActive,
  can,
  open,
  onClose,
}: {
  application: Application;
  environment: Environment;
  scripts: DeploymentScript[];
  primaryCandidates: PrimaryCandidate[];
  sourceControls: SourceControl[];
  deploymentActive: boolean;
  can: { update: boolean; delete: boolean; updateApplication: boolean; deleteApplication: boolean };
  open: boolean;
  onClose: () => void;
}) {
  const dialog = useDialog();
  const runtime = useConfigs()?.application.runtimes.find((preset) => preset.id === application.runtime)?.label ?? application.runtime;
  const ready = application.status === 'ready' && environment.status === 'ready';
  const editable = can.update && ready;
  const deployEditable = editable && !deploymentActive;
  const scriptUrl = `/applications/${application.id}/environments/${environment.id}/deployment-scripts`;

  const content = (
    <>
      {(!ready || !can.update) && (
        <Alert>
          <InfoIcon />
          <AlertDescription>
            {!ready ? 'Settings are read-only until the environment is ready.' : 'You have read-only access to this environment.'}
          </AlertDescription>
        </Alert>
      )}

      <EnvironmentSettingsForm
        key={`environment-${environment.id}`}
        application={application}
        environment={environment}
        primaryCandidates={primaryCandidates}
        disabled={!editable}
      />

      <Section
        title="Deploy scripts"
        description={
          <>
            Plain shell, run in the release directory with your variables plus <code>COMMIT_ID</code>, <code>RELEASE_PATH</code> and{' '}
            <code>BRANCH</code>.
          </>
        }
        action={
          deploymentActive && (
            <Badge variant="outline">
              <LockIcon />
              Locked while deploying
            </Badge>
          )
        }
      >
        {stages.map((stage) => {
          const script = scripts.find((item) => item.name === stage.id);
          const lines = (script?.content ?? '').split('\n').filter((line) => line.trim() !== '');
          return (
            <Row key={stage.id} label={stage.label} description={stage.description}>
              <code className="max-w-56 truncate font-mono text-xs" title={script?.content}>
                {lines.length > 0 ? lines[0] : 'Skipped'}
                {lines.length > 1 && ` +${lines.length - 1}`}
              </code>
              {deployEditable && (
                <Button
                  variant="outline"
                  size="sm"
                  onClick={() =>
                    dialog.environmentDeploymentScript.openNested({
                      url: scriptUrl,
                      stage: stage.id,
                      label: stage.label,
                      script,
                      componentOverride: false,
                    })
                  }
                >
                  <PencilIcon />
                  Edit
                </Button>
              )}
            </Row>
          );
        })}
      </Section>

      <ReleaseSettingsForm key={`releases-${environment.id}`} application={application} environment={environment} disabled={!deployEditable} />

      <ApplicationSettingsForm
        key={`application-${application.id}`}
        application={application}
        runtime={runtime}
        sourceControls={sourceControls}
        disabled={!can.updateApplication || application.status !== 'ready'}
      />

      {(can.delete || can.deleteApplication) && (
        <Section destructive title="Danger zone" description="Deleting removes components from their servers. This cannot be undone.">
          {can.delete && environment.status !== 'deleting' && (
            <Row label={`Delete ${environment.name}`} description="Other environments are not affected.">
              <Button
                variant="destructive"
                size="sm"
                disabled={application.status !== 'ready'}
                onClick={() => dialog.deleteApplicationResource.openNested({ application, environment })}
              >
                {environment.status === 'deletion_failed' ? 'Retry' : 'Delete'}
              </Button>
            </Row>
          )}
          {can.deleteApplication && application.status !== 'deleting' && (
            <Row label={`Delete ${application.name}`} description="Deletes every environment of this application.">
              <Button variant="destructive" size="sm" onClick={() => dialog.deleteApplicationResource.openNested({ application })}>
                {application.status === 'deletion_failed' ? 'Retry' : 'Delete'}
              </Button>
            </Row>
          )}
        </Section>
      )}
    </>
  );

  return (
    <SidePanel
      icon={SettingsIcon}
      title="Settings"
      subtitle={`Configure ${environment.name} and the ${application.name} application.`}
      tabs={[{ value: 'settings', label: 'Settings', content }]}
      onClose={onClose}
      open={open}
      sheet
    />
  );
}
