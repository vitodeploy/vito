import { Link, useForm } from '@inertiajs/react';
import { toast } from 'sonner';
import { InertiaTableData } from '@forjedio/inertia-table-react';
import { BoxIcon, ClockIcon, ListEndIcon, LockIcon, NetworkIcon, PencilIcon, PlusIcon, RefreshCwIcon, RotateCwIcon, TriangleAlertIcon } from 'lucide-react';
import CopyableBadge from '@/components/copyable-badge';
import CopyableField from '@/components/copyable-field';
import ErrorIndicator from '@/components/error-indicator';
import { VitoTable } from '@/components/vito-table';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useDialog } from '@/hooks/use-dialog';
import { humanizeStep, loadBalancerMethodLabel } from '@/lib/utils';
import { Actions as CronJobActions } from '@/pages/cronjobs/components/columns';
import { Actions as WorkerActions } from '@/pages/workers/components/columns';
import {
  Application,
  BackendTarget,
  Component,
  ComponentOptions,
  Environment,
  EnvironmentDomain,
  EnvironmentDomainOptions,
  PrimaryCandidate,
} from '@/types/application';
import { CronJob } from '@/types/cronjob';
import { DeploymentScript } from '@/types/deployment-script';
import { Worker } from '@/types/worker';
import BackendsForm from './backends-form';
import CapabilitiesForm from './capabilities-form';
import ComponentDomains from './component-domains';
import { EmptyState, Row, Section } from './section';
import SidePanel, { PanelTab } from './side-panel';
import { stages } from './stages';
import Status from './status';

export default function ComponentPanel({ application, environment, component, domains, scripts, inheritedScripts, workers, cronjobs, targets, primaryCandidates, deploymentActive, capabilities, domainOptions, can, onClose }: {
  application: Application;
  environment: Environment;
  component: Component;
  domains: EnvironmentDomain[];
  scripts: DeploymentScript[];
  inheritedScripts: DeploymentScript[];
  workers: InertiaTableData | null;
  cronjobs: InertiaTableData | null;
  targets: BackendTarget[];
  primaryCandidates: PrimaryCandidate[];
  deploymentActive: boolean;
  capabilities: ComponentOptions['capabilities'];
  domainOptions: EnvironmentDomainOptions;
  can: { updateComponent: boolean; deleteComponent: boolean };
  onClose: () => void;
}) {
  const dialog = useDialog();
  const retry = useForm({});
  const componentUrl = `/applications/${application.id}/environments/${environment.id}/components/${component.id}`;
  const loadBalancer = component.type === 'load_balancer';
  const parentsReady = application.status === 'ready' && environment.status === 'ready';
  const editable = can.updateComponent && parentsReady && component.status === 'ready';
  const primary = environment.primary_component_id === component.id;
  const capabilityLabel = (id: string) => capabilities.find((capability) => capability.id === id)?.label ?? humanizeStep(id);
  const owner = { user: component.user, url: componentUrl };
  const hasDomains = loadBalancer || component.capabilities.includes('web') || domains.length > 0;
  const hasProcesses = component.surfaces.includes('workers') || component.surfaces.includes('cron_jobs');

  const overview = (
    <>
      {component.status === 'installation_failed' && (
        <Alert variant="destructive">
          <TriangleAlertIcon />
          <AlertTitle>Installation failed</AlertTitle>
          <AlertDescription className="flex flex-col items-start gap-3">
            {component.last_error && <p className="break-words">{component.last_error}</p>}
            {can.updateComponent && parentsReady && (
              <Button size="sm" variant="outline" disabled={retry.processing} onClick={() => retry.post(`${componentUrl}/retry`, { preserveScroll: true, preserveState: true, onError: (errors) => toast.error(errors.component ?? 'The installation could not be retried.') })}>
                <RotateCwIcon />Retry installation
              </Button>
            )}
          </AlertDescription>
        </Alert>
      )}
      {component.status !== 'installation_failed' && component.last_error && (
        <Alert variant="destructive"><TriangleAlertIcon /><AlertDescription className="break-words">{component.last_error}</AlertDescription></Alert>
      )}
      {component.needs_redeploy && (
        <Alert>
          <RefreshCwIcon />
          <AlertTitle>Changes are waiting to be deployed</AlertTitle>
          <AlertDescription>Configuration changed since the last deployment. Deploy to apply it.</AlertDescription>
        </Alert>
      )}
      <Section title="Details">
        <Row label="Status"><Status status={component.status} color={component.status_color} /></Row>
        <Row label="Server"><Link href={`/servers/${component.server_id}`} className="hover:text-foreground hover:underline">{component.server_name}</Link></Row>
        <Row label="Run as"><span className="font-mono">{component.user}</span></Row>
        {primary && <Row label="Role"><Badge variant="info">Primary</Badge></Row>}
        {loadBalancer && <Row label="Balancing">{loadBalancerMethodLabel(component.load_balancer_method)} · {component.backends?.length === 1 ? '1 backend' : `${component.backends?.length ?? 0} backends`}</Row>}
        {!loadBalancer && <Row label="Path"><CopyableBadge text={component.path} /></Row>}
      </Section>
      {!loadBalancer && (
        <Section title="Capabilities" description="What this component runs from each release.">
          {component.capability_configs.length === 0 ? (
            <EmptyState className="rounded-none border-0" icon={BoxIcon} title="Nothing runs yet" description="Turn capabilities on in Settings." />
          ) : component.capability_configs.map((capability) => {
            const summary = (capabilities.find((item) => item.id === capability.capability)?.form ?? [])
              .filter((field) => ['text', 'textarea', 'select'].includes(field.type) && ['string', 'number'].includes(typeof capability.config[field.name]) && capability.config[field.name] !== '')
              .map((field) => String(capability.config[field.name]));
            return (
              <Row key={capability.capability} label={capabilityLabel(capability.capability)} description={summary.length > 0 && <span className="font-mono text-xs">{summary.join(' · ')}</span>}>
                <Status status={capability.status} color={capability.status_color} />
              </Row>
            );
          })}
        </Section>
      )}
      {!loadBalancer && component.ssh_key && (
        <Section title="Deploy key" description="Add it as a read-only deploy key if your repository needs one.">
          <div className="p-4"><CopyableField value={component.ssh_key} /></div>
        </Section>
      )}
    </>
  );

  const processes = (
    <>
      {component.surfaces.includes('workers') && workers && (
        <Section
          title="Workers"
          description="Long-running processes started from the live release."
          action={editable && component.capabilities.includes('worker') && <Button size="sm" variant="outline" onClick={() => dialog.workerForm.open({ serverId: component.server_id, component: owner })}><PlusIcon />Add worker</Button>}
        >
          {workers.data.length === 0 ? (
            <EmptyState className="rounded-none border-0" icon={ListEndIcon} title="No workers" description="Queue consumers and daemons run here once deployed." />
          ) : (
            <VitoTable
              modal
              tableData={{ ...workers, searchable: false }}
              cellRenderers={{
                status: ({ row }) => {
                  const worker = row.worker as Worker;
                  return <span className="flex items-center gap-1.5"><Status status={worker.status} color={worker.status_color} className="text-xs" /><ErrorIndicator error={worker.error} label={`Worker "${worker.name}" error`} /></span>;
                },
              }}
              actions={(row) => <WorkerActions worker={row.worker as Worker} canManage={editable} />}
            />
          )}
        </Section>
      )}
      {component.surfaces.includes('cron_jobs') && cronjobs && (
        <Section
          title="Cron jobs"
          description="Scheduled commands run as the component user."
          action={editable && <Button size="sm" variant="outline" onClick={() => dialog.cronjobForm.open({ serverId: component.server_id, component: owner })}><PlusIcon />Add cron job</Button>}
        >
          {cronjobs.data.length === 0 ? (
            <EmptyState className="rounded-none border-0" icon={ClockIcon} title="No cron jobs" description="Schedule commands against the live release." />
          ) : (
            <VitoTable modal tableData={{ ...cronjobs, searchable: false }} actions={(row) => <CronJobActions cronJob={row.cronjob as CronJob} canManage={editable} />} />
          )}
        </Section>
      )}
    </>
  );

  const settings = (
    <>
      {!loadBalancer && <CapabilitiesForm key={component.id} application={application} environment={environment} component={component} capabilities={capabilities} disabled={!editable} />}
      {!loadBalancer && (
        <Section title="Deploy scripts" description="Override the environment's scripts for this component only." action={deploymentActive && <Badge variant="outline"><LockIcon />Locked while deploying</Badge>}>
          {stages.map((stage) => {
            const script = scripts.find((item) => item.name === stage.id);
            const inheritedScript = inheritedScripts.find((item) => item.name === stage.id);
            return (
              <Row key={stage.id} label={stage.label} description={script ? 'Custom script' : 'Uses the environment script'}>
                {editable && !deploymentActive && (
                  <Button size="sm" variant="outline" onClick={() => dialog.environmentDeploymentScript.open({ url: `${componentUrl}/deployment-scripts`, stage: stage.id, label: `${stage.label} · ${component.name}`, script, inheritedScript, componentOverride: true })}>
                    <PencilIcon />Edit
                  </Button>
                )}
              </Row>
            );
          })}
        </Section>
      )}
      {can.deleteComponent && component.status !== 'deleting' && (
        <Section destructive title="Danger zone">
          <Row label="Delete component" description="Removes its files, deploy key and processes from the server.">
            <Button variant="destructive" size="sm" onClick={() => dialog.deleteComponent.open({ application, environment, component, primaryCandidates })}>
              {component.status === 'deletion_failed' ? 'Retry' : 'Delete'}
            </Button>
          </Row>
        </Section>
      )}
    </>
  );

  const tabs: PanelTab[] = [
    { value: 'overview', label: 'Overview', content: overview },
    ...(loadBalancer ? [{
      value: 'backends', label: 'Backends', content: <BackendsForm key={component.id} application={application} environment={environment} component={component} targets={targets} disabled={!editable} />,
    }] : []),
    ...(hasDomains ? [{
      value: 'domains', label: 'Domains', content: <ComponentDomains application={application} environment={environment} component={component} domains={domains} options={domainOptions} editable={can.updateComponent && parentsReady} />,
    }] : []),
    ...(hasProcesses ? [{ value: 'processes', label: 'Processes', content: processes }] : []),
    ...(!loadBalancer || (can.deleteComponent && component.status !== 'deleting') ? [{ value: 'settings', label: 'Settings', content: settings }] : []),
  ];

  return (
    <SidePanel
      key={component.id}
      icon={loadBalancer ? NetworkIcon : BoxIcon}
      title={component.name}
      subtitle={<>
        <Status status={component.status} color={component.status_color} />
        <span>{loadBalancer ? 'Load balancer' : 'App'} on {component.server_name}</span>
      </>}
      tabs={tabs}
      onClose={onClose}
    />
  );
}
