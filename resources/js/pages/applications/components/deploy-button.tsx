import { FormEvent, useId } from 'react';
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon, RocketIcon } from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { Application, Environment } from '@/types/application';

export default function DeployButton({ application, environment }: { application: Application; environment: Environment }) {
  const form = useForm<Record<string, string>>({});
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

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (!ready) return;
    form.post(`/applications/${application.id}/environments/${environment.id}/deployments`, {
      preserveScroll: true,
      onError: (errors) => toast.error(errors.deployment ?? 'The deployment could not be started.'),
    });
  };

  return (
    <form onSubmit={submit}>
      <Tooltip>
        <TooltipTrigger asChild>
          <Button
            type="submit"
            size="sm"
            disabled={form.processing}
            aria-disabled={!ready}
            aria-describedby={blocker ? blockerId : undefined}
            className="relative aria-disabled:cursor-not-allowed aria-disabled:opacity-50"
          >
            {form.processing ? <LoaderCircleIcon className="animate-spin" /> : <RocketIcon />}
            Deploy
            {environment.needs_redeploy && ready && <span className="bg-warning ring-background absolute -top-1 -right-1 size-2.5 rounded-full ring-2"><span className="sr-only">Changes waiting to be deployed</span></span>}
          </Button>
        </TooltipTrigger>
        <TooltipContent>{blocker ?? (environment.needs_redeploy ? 'Changes are waiting to be deployed' : `Deploy ${environment.branch}`)}</TooltipContent>
      </Tooltip>
      {blocker && <span id={blockerId} className="sr-only">{blocker}</span>}
    </form>
  );
}
