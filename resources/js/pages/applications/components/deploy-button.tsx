import { useId } from 'react';
import { RocketIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useDialog } from '@/hooks/use-dialog';
import { Application, Environment } from '@/types/application';

export default function DeployButton({ application, environment }: { application: Application; environment: Environment }) {
  const dialog = useDialog();
  const blockerId = useId();
  if (!environment.can_deploy) return null;
  const blocker = application.status !== 'ready' || environment.status !== 'ready'
    ? 'Available when the environment is ready'
    : (environment.components_count ?? 0) === 0
      ? 'Add a component first'
      : !environment.primary_component_id
        ? 'Choose a primary component in Settings'
        : null;
  const ready = blocker === null;

  const deploy = () => {
    if (!ready) return;
    dialog.confirm.open({
      title: `Deploy ${environment.name}`,
      description: `Builds the latest commit on ${environment.branch} and releases it to every component in ${environment.name}.`,
      confirmLabel: 'Deploy',
      method: 'post',
      url: `/applications/${application.id}/environments/${environment.id}/deployments`,
    });
  };

  return (
    <>
      <Tooltip>
        <TooltipTrigger asChild>
          <Button
            size="sm"
            onClick={deploy}
            aria-disabled={!ready}
            aria-describedby={blocker ? blockerId : undefined}
            className="relative aria-disabled:cursor-not-allowed aria-disabled:opacity-50"
          >
            <RocketIcon />
            Deploy
            {environment.needs_redeploy && ready && <span className="bg-warning ring-background absolute -top-1 -right-1 size-2.5 rounded-full ring-2"><span className="sr-only">Changes waiting to be deployed</span></span>}
          </Button>
        </TooltipTrigger>
        <TooltipContent>{blocker ?? (environment.needs_redeploy ? 'Changes are waiting to be deployed' : `Deploy ${environment.branch}`)}</TooltipContent>
      </Tooltip>
      {blocker && <span id={blockerId} className="sr-only">{blocker}</span>}
    </>
  );
}
