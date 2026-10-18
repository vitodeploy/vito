import { Head, Link, useForm, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import InputError from '@/components/ui/input-error';
import { RotateCwIcon, Trash2Icon } from 'lucide-react';
import EnvironmentLayout from '@/layouts/environment/layout';
import { useDialog } from '@/hooks/use-dialog';
import { Application, Component, Environment, PrimaryCandidate } from '@/types/application';

export default function ShowComponent() {
  const { application, environment, component, primaryCandidates, can } = usePage<{
    application: Application;
    environment: Environment;
    component: Component;
    primaryCandidates: PrimaryCandidate[];
    can: { update: boolean; delete: boolean };
  }>().props;
  const dialog = useDialog();
  const retry = useForm({});
  const baseUrl = `/applications/${application.id}/environments/${environment.id}/components`;

  return (
    <EnvironmentLayout>
      <Head title={`${component.name} - ${environment.name}`} />
      <Container className="max-w-5xl">
        <HeaderContainer>
          <div className="flex items-center gap-3">
            <Heading title={component.name} description={environment.name} />
            <Badge variant={component.status_color}>{component.status}</Badge>
          </div>
          <div className="flex items-center gap-2">
            {can.update && component.status === 'installation_failed' && application.status === 'ready' && environment.status === 'ready' && <Button variant="outline" disabled={retry.processing} onClick={() => retry.post(`${baseUrl}/${component.id}/retry`)}><RotateCwIcon />Retry installation</Button>}
            {can.delete && component.status !== 'deleting' && <Button variant="destructive" onClick={() => dialog.deleteComponent.open({ application, environment, component, primaryCandidates })}><Trash2Icon />{component.status === 'deletion_failed' ? 'Retry deletion' : 'Delete'}</Button>}
          </div>
        </HeaderContainer>
        <InputError message={(retry.errors as Record<string, string>).component} />
        {component.last_error && <p role="alert" className="text-destructive text-sm">{component.last_error}</p>}
        {component.needs_redeploy && <p className="text-muted-foreground text-sm">Repository changes require a new deployment.</p>}
        <Card>
          <CardContent className="flex flex-col gap-4 p-4">
            <dl className="grid gap-4 sm:grid-cols-3">
              <div><dt className="text-muted-foreground text-sm">Server</dt><dd><Link className="hover:underline" href={`/servers/${component.server_id}`}>{component.server_name}</Link></dd></div>
              <div><dt className="text-muted-foreground text-sm">Linux user</dt><dd>{component.user}</dd></div>
              <div><dt className="text-muted-foreground text-sm">Type</dt><dd>{component.type}</dd></div>
              <div className="sm:col-span-3"><dt className="text-muted-foreground text-sm">Path</dt><dd className="break-all font-mono text-sm">{component.path}</dd></div>
              <div className="sm:col-span-3"><dt className="text-muted-foreground text-sm">Capabilities</dt><dd>{component.capabilities.join(', ') || 'None'}</dd></div>
            </dl>
            <div className="flex flex-col gap-2">
              <h2 className="font-medium">Deploy public key</h2>
              {component.ssh_key ? <pre className="bg-muted overflow-x-auto rounded-md p-3 text-sm">{component.ssh_key}</pre> : <p className="text-muted-foreground text-sm">No deploy key. GitHub App sources do not need one; other sources create it during installation.</p>}
            </div>
          </CardContent>
        </Card>
      </Container>
    </EnvironmentLayout>
  );
}
