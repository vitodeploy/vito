import { Link, usePage } from '@inertiajs/react';
import { CheckIcon, FileTextIcon, GitCommitHorizontalIcon, LoaderCircleIcon, MinusIcon, RocketIcon, XIcon } from 'lucide-react';
import DateTime from '@/components/date-time';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useDialog } from '@/hooks/use-dialog';
import { cn } from '@/lib/utils';
import { Application, ComponentDeployment, DeploymentStage, Environment, EnvironmentDeployment } from '@/types/application';
import { Row, Section } from './section';
import SidePanel from './side-panel';
import Status from './status';

const steps: { stage: DeploymentStage; label: string }[] = [
  { stage: 'clone', label: 'Clone' },
  { stage: 'build', label: 'Build' },
  { stage: 'Release command', label: 'Release cmd' },
  { stage: 'release', label: 'Release' },
  { stage: 'post-release', label: 'Post-release' },
];

type StepState = 'done' | 'running' | 'failed' | 'waiting' | 'skipped';

function stepState(run: EnvironmentDeployment, component: ComponentDeployment, stage: DeploymentStage): StepState {
  if (component.component_type === 'load_balancer' && stage !== 'release') return 'skipped';
  if (run.rollback_of_id && stage !== 'release' && stage !== 'post-release') return 'skipped';
  if (stage === 'Release command' && (run.is_join || component.component_id !== run.primary_component_id)) return 'skipped';
  const current = component.current_stage ? steps.findIndex((step) => step.stage === component.current_stage) : -1;
  const index = steps.findIndex((step) => step.stage === stage);
  if (index < current) return 'done';
  if (index > current) return 'waiting';
  return component.status === 'deploying' ? 'running' : component.status === 'failed' ? 'failed' : 'done';
}

const stepStyles: Record<StepState, string> = {
  done: 'bg-success text-background border-success',
  running: 'border-warning text-warning',
  failed: 'bg-destructive text-background border-destructive',
  waiting: 'text-muted-foreground',
  skipped: 'text-muted-foreground border-dashed',
};

function Stepper({ run, component }: { run: EnvironmentDeployment; component: ComponentDeployment }) {
  return (
    <ol className="flex items-start">
      {steps.map((step, index) => {
        const state = stepState(run, component, step.stage);
        return (
          <li key={step.stage} className="flex flex-1 flex-col items-center gap-1.5">
            <div className="flex w-full items-center">
              <span className={cn('h-px flex-1', index > 0 && 'bg-border')} />
              <Tooltip>
                <TooltipTrigger asChild>
                  <span className={cn('flex size-6 shrink-0 items-center justify-center rounded-full border', stepStyles[state])}>
                    {state === 'done' && <CheckIcon className="size-3.5" />}
                    {state === 'running' && <LoaderCircleIcon className="size-3.5 animate-spin" />}
                    {state === 'failed' && <XIcon className="size-3.5" />}
                    {state === 'skipped' && <MinusIcon className="size-3" />}
                  </span>
                </TooltipTrigger>
                <TooltipContent aria-hidden="true">
                  {step.label}: {state === 'running' && run.is_recovering && step.stage === 'release' ? 'restoring' : state}
                </TooltipContent>
              </Tooltip>
              <span className={cn('h-px flex-1', index < steps.length - 1 && 'bg-border')} />
            </div>
            <span className="text-muted-foreground text-center text-[11px] leading-tight">
              {step.label}
              <span className="sr-only">: {state}</span>
            </span>
          </li>
        );
      })}
    </ol>
  );
}

export default function DeploymentPanel({
  application,
  environment,
  deployment,
  onClose,
}: {
  application: Application;
  environment: Environment;
  deployment: EnvironmentDeployment;
  onClose: () => void;
}) {
  const dialog = useDialog();
  const baseUrl = `/applications/${application.id}/environments/${environment.id}`;
  const query = usePage().url.split('?')[1];
  const live = environment.live_run_id === deployment.id;
  const canRollback = environment.can_deploy && deployment.status === 'finished' && !deployment.is_join && !live;
  const message = deployment.commit_data?.message?.split('\n')[0];

  const overview = (
    <>
      {deployment.last_error && (
        <Alert variant="destructive">
          <AlertDescription className="break-words">{deployment.last_error}</AlertDescription>
        </Alert>
      )}
      {deployment.status === 'cancelled' && deployment.stage_deadline_at && (
        <Alert>
          <AlertDescription>Waiting for in-flight work to finish before the next deployment starts.</AlertDescription>
        </Alert>
      )}
      {deployment.is_recovering && (
        <Alert>
          <AlertDescription>
            {deployment.status === 'running' && 'A release failed, so Vito is restoring the previous release on these components.'}
            {deployment.status === 'rolled_back' && 'A release failed, so Vito restored the previous release on every component.'}
            {deployment.status === 'partial' &&
              'A release failed and Vito could not restore the previous release on every component. Check the failed components before deploying again.'}
          </AlertDescription>
        </Alert>
      )}
      <Section title="Details">
        <Row label="Commit">
          <GitCommitHorizontalIcon className="size-4" />
          <span className="font-mono">{deployment.commit_id?.slice(0, 7) ?? 'Resolving'}</span>
        </Row>
        <Row label="Branch">
          <span className="font-mono">{deployment.branch}</span>
        </Row>
        <Row label="Triggered by">{deployment.triggered_by ?? (deployment.is_join ? 'Vito' : 'Git push')}</Row>
        <Row label="Started">
          <DateTime date={deployment.created_at} relative />
        </Row>
        <Row label="Release">
          <span className="font-mono text-xs">{deployment.release}</span>
        </Row>
      </Section>
      <Section title="Components" description="Each component runs these stages in order.">
        {deployment.deployments.length === 0 ? (
          <p className="text-muted-foreground px-4 py-6 text-center text-sm">
            {['pending', 'running'].includes(deployment.status) ? 'Waiting for the run to start…' : 'No components were deployed.'}
          </p>
        ) : (
          deployment.deployments.map((component) => (
            <div key={component.id} className="flex flex-col gap-4 px-4 py-3">
              <Row
                className="min-h-0 p-0"
                label={
                  <>
                    {component.component_id ? (
                      <Link href={`${baseUrl}?component=${component.component_id}`} className="hover:underline">
                        {component.component_name}
                      </Link>
                    ) : (
                      <span className="text-muted-foreground">
                        {component.component_name ? `${component.component_name} (removed)` : 'Removed component'}
                      </span>
                    )}
                    {component.server_name && component.server_name !== component.component_name && (
                      <span className="text-muted-foreground font-normal"> on {component.server_name}</span>
                    )}
                  </>
                }
              >
                <Status status={component.status} color={component.status_color} />
                {component.server_id && component.log_id && (
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() =>
                      dialog.logViewer.open({
                        serverId: component.server_id!,
                        logId: component.log_id!,
                        title: `${component.component_name} · deployment #${deployment.id}`,
                      })
                    }
                  >
                    <FileTextIcon />
                    Logs
                  </Button>
                )}
              </Row>
              <Stepper run={deployment} component={component} />
            </div>
          ))
        )}
      </Section>
    </>
  );

  return (
    <SidePanel
      key={deployment.id}
      icon={RocketIcon}
      title={message || `Deployment #${deployment.id}`}
      subtitle={
        <>
          <Status status={deployment.status} color={deployment.status_color} />
          <span>#{deployment.id}</span>
          {live && <Badge variant="success">Live</Badge>}
          {deployment.rollback_of_id && <Badge variant="info">Rollback of #{deployment.rollback_of_id}</Badge>}
          {deployment.is_join && <Badge variant="info">Join</Badge>}
        </>
      }
      actions={
        <>
          {environment.can_deploy && deployment.cancellable && (
            <Button
              variant="outline"
              size="sm"
              onClick={() =>
                dialog.confirm.open({
                  title: 'Cancel deployment',
                  description: 'No further stages will start. Work already running finishes first.',
                  confirmLabel: 'Cancel deployment',
                  method: 'post',
                  url: `${baseUrl}/deployments/${deployment.id}/cancel`,
                })
              }
            >
              Cancel
            </Button>
          )}
          {canRollback && (
            <Tooltip>
              <TooltipTrigger asChild>
                <span tabIndex={deployment.rollback_available ? undefined : 0}>
                  <Button
                    variant="outline"
                    size="sm"
                    disabled={!deployment.rollback_available}
                    onClick={() =>
                      dialog.confirm.open({
                        title: `Roll back to #${deployment.id}`,
                        description:
                          'Switches every component back to this release, runs the post-release script and restarts processes. Variables stay as they are, release-command effects (like migrations) are not undone, and any pending deployment is replaced.',
                        confirmLabel: 'Roll back',
                        method: 'post',
                        url: `${baseUrl}/deployments/${deployment.id}/rollback${query ? `?${query}` : ''}`,
                      })
                    }
                  >
                    Roll back
                  </Button>
                </span>
              </TooltipTrigger>
              <TooltipContent>
                {deployment.rollback_available ? 'Make this release live again' : 'This release is no longer on every component'}
              </TooltipContent>
            </Tooltip>
          )}
        </>
      }
      tabs={[{ value: 'overview', label: 'Overview', content: overview }]}
      onClose={onClose}
      sheet
    />
  );
}
