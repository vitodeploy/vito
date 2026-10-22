import { Link } from '@inertiajs/react';
import { DatabaseIcon, RotateCwIcon, TriangleAlertIcon, ZapIcon } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { useDialog } from '@/hooks/use-dialog';
import { Application, Environment, EnvironmentAttachment } from '@/types/application';
import { panelProps } from './canvas';
import { Row, Section } from './section';
import SidePanel from './side-panel';
import Status from './status';

export default function ResourcePanel({ application, environment, resource, editable, onClose }: {
  application: Application;
  environment: Environment;
  resource: EnvironmentAttachment;
  editable: boolean;
  onClose: () => void;
}) {
  const dialog = useDialog();
  const baseUrl = `/applications/${application.id}/environments/${environment.id}`;
  const url = `${baseUrl}/resources/${resource.id}`;
  const name = resource.name ?? 'Unavailable resource';
  const kind = resource.kind === 'database' ? 'Database' : 'Redis / Valkey';

  const overview = (
    <>
      {resource.last_error && (
        <Alert variant="destructive">
          <TriangleAlertIcon />
          <AlertDescription className="flex flex-col items-start gap-3">
            <p className="break-words">{resource.last_error}</p>
            {editable && ['failed', 'deletion_failed'].includes(resource.status) && (
              <Button size="sm" variant="outline" onClick={() => dialog.confirm.open({
                title: 'Retry access sync', description: 'Retries the failed sync or cleanup. Stored data is not touched.', confirmLabel: 'Retry', method: 'post', url: `${url}/retry`,
              })}><RotateCwIcon />Retry</Button>
            )}
          </AlertDescription>
        </Alert>
      )}
      <Section title="Details">
        <Row label="Status"><Status status={resource.status} color={resource.status_color} /></Row>
        <Row label="Type">{kind}</Row>
        <Row label="Name"><span className="font-mono">{name}</span></Row>
        <Row label="Server"><Link href={`/servers/${resource.server_id}`} className="hover:text-foreground hover:underline">{resource.server_name}</Link></Row>
        <Row label="Variable prefix">{resource.alias ? <span className="font-mono">{resource.alias}</span> : 'None'}</Row>
      </Section>
      <Section
        title="Connection"
        description="Vito manages a dedicated user and firewall access for every app component, and injects the connection variables on the next deploy."
        action={<Button variant="outline" size="sm" asChild><Link href={`${baseUrl}/variables`} preserveState preserveScroll only={panelProps}>View variables</Link></Button>}
      />
      {editable && !['deleting', 'deletion_failed'].includes(resource.status) && (
        <Section destructive title="Danger zone">
          <Row label="Detach resource" description="Removes the managed user and firewall rules. The data itself is kept.">
            <Button variant="destructive" size="sm" onClick={() => dialog.confirm.open({
              title: `Detach ${name}`, description: 'Apps using these credentials lose access until you redeploy without it. The database and its data are kept.',
              variant: 'destructive', confirmLabel: 'Detach', method: 'delete', url,
            })}>Detach</Button>
          </Row>
        </Section>
      )}
    </>
  );

  return (
    <SidePanel
      key={resource.id}
      icon={resource.kind === 'database' ? DatabaseIcon : ZapIcon}
      title={resource.alias ? `${resource.alias} · ${name}` : name}
      subtitle={<><Status status={resource.status} color={resource.status_color} /><span>{kind} on {resource.server_name}</span></>}
      tabs={[{ value: 'overview', label: 'Overview', content: overview }]}
      onClose={onClose}
    />
  );
}
