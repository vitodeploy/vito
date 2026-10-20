import { FormEvent } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { PencilIcon } from 'lucide-react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Form, FormField } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { useDialog } from '@/hooks/use-dialog';
import EnvironmentLayout from '@/layouts/environment/layout';
import { Application, Environment, PrimaryCandidate } from '@/types/application';
import { DeploymentScript } from '@/types/deployment-script';

const stages = [
  { id: 'build', label: 'Build', description: 'Runs on every component using the staged environment file.' },
  { id: 'pre-release', label: 'Release command', description: 'Runs once on the primary before any component switches. Skipped for live-release joins.' },
  { id: 'post-release', label: 'Post-release', description: 'Runs on every component with the live environment file. A failure does not roll back the release.' },
];

export default function DeploymentSettings() {
  const { application, environment, scripts, inheritedScripts = [], scriptComponent, scriptComponents, canUpdate, deploymentActive } = usePage<{
    application: Application;
    environment: Environment;
    scripts: DeploymentScript[];
    inheritedScripts?: DeploymentScript[];
    scriptComponent?: PrimaryCandidate;
    scriptComponents: PrimaryCandidate[];
    canUpdate: boolean;
    deploymentActive: boolean;
  }>().props;
  const dialog = useDialog();
  const baseUrl = `/applications/${application.id}/environments/${environment.id}`;
  const scriptUrl = scriptComponent ? `${baseUrl}/components/${scriptComponent.id}/deployment-scripts` : `${baseUrl}/deployment-scripts`;
  const editable = canUpdate && !deploymentActive && application.status === 'ready' && environment.status === 'ready';
  const form = useForm({ shared_paths: environment.shared_paths.join('\n'), keep_releases: environment.keep_releases });
  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.transform((data) => ({ ...data, shared_paths: data.shared_paths.split('\n').map((path) => path.trim()).filter(Boolean) }));
    form.put(`${baseUrl}/deployment-settings`, { preserveScroll: true });
  };

  return (
    <EnvironmentLayout>
      <Head title={`Deployment settings - ${environment.name}`} />
      <Container className="max-w-5xl">
        <Heading title={scriptComponent ? `${scriptComponent.name} scripts` : 'Deployment settings'} description="Scripts are plain shell. Component overrides replace, rather than append to, the environment script." badges={<Badge variant="warning">Beta</Badge>} />
        {deploymentActive && <Alert><AlertDescription>Scripts and settings are locked while a deployment is running or queued. Variable edits remain available for the next deploy.</AlertDescription></Alert>}
        {stages.map((stage) => {
          const script = scripts.find((item) => item.name === stage.id);
          const inheritedScript = inheritedScripts.find((item) => item.name === stage.id);
          return <Card key={stage.id}>
            <CardHeader className="flex-row items-center justify-between gap-2">
              <CardTitle>{stage.label}{scriptComponent && !script ? ' · Inherited' : ''}</CardTitle>
              {editable && <Button variant="outline" size="sm" onClick={() => dialog.environmentDeploymentScript.open({ url: scriptUrl, stage: stage.id, label: stage.label, script, inheritedScript, componentOverride: !!scriptComponent })}><PencilIcon />Edit</Button>}
            </CardHeader>
            <CardContent className="flex flex-col gap-3 p-4">
              <p className="text-muted-foreground text-sm">{stage.description}</p>
              <pre className="bg-muted max-h-48 overflow-auto rounded-md p-3 text-sm">{(script ?? inheritedScript)?.content || 'No commands. This stage is skipped.'}</pre>
            </CardContent>
          </Card>;
        })}
        {!scriptComponent && <>
          <Form onSubmit={submit}>
            <FormField>
              <Label htmlFor="shared-paths">Shared paths</Label>
              <Textarea id="shared-paths" value={form.data.shared_paths} disabled={!editable} onChange={(e) => form.setData('shared_paths', e.target.value)} rows={5} />
              <p className="text-muted-foreground text-sm">One relative path per line. Content persists across releases. Parent traversal, overlapping paths and managed .env files are not allowed.</p>
              <InputError message={form.errors.shared_paths} />
              {Object.entries(form.errors).filter(([key]) => key.startsWith('shared_paths.')).map(([key, message]) => <InputError key={key} message={message} />)}
            </FormField>
            <FormField>
              <Label htmlFor="keep-releases">Releases to keep</Label>
              <Input id="keep-releases" type="number" min={1} max={100} value={form.data.keep_releases} disabled={!editable} onChange={(e) => form.setData('keep_releases', Number(e.target.value))} />
              <p className="text-muted-foreground text-sm">The active release is always kept, even when it is older than this limit.</p>
              <InputError message={form.errors.keep_releases} />
            </FormField>
            {editable && <div><Button type="submit" disabled={form.processing}>Save settings</Button></div>}
          </Form>
          {scriptComponents.length > 0 && <div className="flex flex-col gap-2">
            <h2 className="font-medium">Component script overrides</h2>
            {scriptComponents.map((component) => <Link key={component.id} href={`${baseUrl}/components/${component.id}/deployment-scripts`} className="text-sm hover:underline">{component.name}</Link>)}
          </div>}
        </>}
        <Link href={`${baseUrl}/${scriptComponent ? 'deployment-settings' : 'deployments'}`} className="text-sm hover:underline">{scriptComponent ? 'Back to environment settings' : 'Back to deployments'}</Link>
      </Container>
    </EnvironmentLayout>
  );
}
