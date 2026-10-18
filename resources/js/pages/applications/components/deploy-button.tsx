import { FormEvent } from 'react';
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon, RocketIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import InputError from '@/components/ui/input-error';
import { Application, Environment } from '@/types/application';

export default function DeployButton({ application, environment }: { application: Application; environment: Environment }) {
  const form = useForm<Record<string, string>>({});
  if (!environment.can_deploy || application.status !== 'ready' || environment.status !== 'ready') return null;

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(`/applications/${application.id}/environments/${environment.id}/deployments`);
  };

  return (
    <form onSubmit={submit} className="flex flex-col items-start gap-2">
      <Button type="submit" disabled={form.processing}>
        {form.processing ? <LoaderCircleIcon className="animate-spin" /> : <RocketIcon />}Deploy
      </Button>
      <InputError message={form.errors.deployment} />
    </form>
  );
}
