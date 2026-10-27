import { useCallback, useEffect, useRef } from 'react';
import { toast } from 'sonner';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { InertiaTableData } from '@forjedio/inertia-table-react';
import {
  BoxIcon,
  DatabaseIcon,
  HistoryIcon,
  KeyRoundIcon,
  LayersIcon,
  NetworkIcon,
  PlusIcon,
  RocketIcon,
  SettingsIcon,
  TriangleAlertIcon,
  ZapIcon,
} from 'lucide-react';
import DateTime from '@/components/date-time';
import { cn } from '@/lib/utils';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useDialog } from '@/hooks/use-dialog';
import ApplicationLayout from '@/layouts/application/layout';
import Canvas, { panelProps } from '@/pages/applications/components/canvas';
import ComponentPanel from '@/pages/applications/components/component-panel';
import DeployButton from '@/pages/applications/components/deploy-button';
import DocsLink from '@/pages/applications/components/docs-link';
import ResourcePanel from '@/pages/applications/components/resource-panel';
import Status from '@/pages/applications/components/status';
import { useDialogStore } from '@/stores/dialog-store';
import {
  Application,
  BackendTarget,
  Component,
  ComponentOptions,
  Environment,
  EnvironmentAttachment,
  EnvironmentDeployment,
  EnvironmentDeploymentSummary,
  EnvironmentDomain,
  EnvironmentDomainOptions,
  EnvironmentResourceOptions,
  EnvironmentTopology,
  EnvironmentVariable,
  PrimaryCandidate,
} from '@/types/application';
import { DeploymentScript } from '@/types/deployment-script';
import { SourceControl } from '@/types/source-control';

export type EnvironmentPage = {
  application: Application;
  environment: Environment;
  topology: EnvironmentTopology;
  lastDeployment: EnvironmentDeploymentSummary | null;
  warnings: string[];
  primaryCandidates: PrimaryCandidate[];
  deploymentActive: boolean;
  options?: ComponentOptions | null;
  resourceOptions?: EnvironmentResourceOptions | null;
  capabilities: ComponentOptions['capabilities'];
  domainOptions: EnvironmentDomainOptions | null;
  component: Component | null;
  componentDomains: EnvironmentDomain[];
  componentScripts: DeploymentScript[];
  inheritedScripts: DeploymentScript[];
  workers: InertiaTableData | null;
  cronjobs: InertiaTableData | null;
  backendTargets: BackendTarget[];
  resource: EnvironmentAttachment | null;
  sheet: 'settings' | 'variables' | 'deployments' | null;
  scripts: DeploymentScript[];
  sourceControls: SourceControl[];
  variables: EnvironmentVariable[];
  deployments: InertiaTableData | null;
  deployment: EnvironmentDeployment | null;
  can: {
    update: boolean;
    delete: boolean;
    updateApplication: boolean;
    deleteApplication: boolean;
    createComponent: boolean;
    updateComponent: boolean;
    deleteComponent: boolean;
  };
};

const chip = 'bg-background flex min-h-9 items-center gap-2 rounded-md border px-3 py-1.5 text-sm shadow-xs';
const sheets = [
  { path: 'variables', label: 'Variables', icon: KeyRoundIcon },
  { path: 'deployments', label: 'Deployments', icon: HistoryIcon },
  { path: 'settings', label: 'Settings', icon: SettingsIcon },
];

export default function ShowEnvironment() {
  const { props, url } = usePage<EnvironmentPage>();
  const { application, environment, topology, lastDeployment, warnings, component, resource, can } = props;
  const dialog = useDialog();
  const baseUrl = `/applications/${application.id}/environments/${environment.id}`;
  const ready = application.status === 'ready' && environment.status === 'ready';
  const selection = component ? `?component=${component.id}` : resource ? `?resource=${resource.id}` : '';
  const close = useCallback(() => router.visit(baseUrl, { preserveState: true, preserveScroll: true, only: panelProps }), [baseUrl]);
  const deploymentsList = useRef<string | null>(null);
  const hasDeployments = props.deployments !== null;
  const deploymentId = props.deployment?.id;
  useEffect(() => {
    if (props.sheet !== 'deployments') deploymentsList.current = null;
    if (!props.sheet || (props.sheet === 'deployments' && !hasDeployments)) return;
    if (props.sheet === 'deployments' && !deploymentId) deploymentsList.current = baseUrl;
    dialog.environmentSheet.open({ returnToList: deploymentsList.current === baseUrl });
    return () => {
      if (useDialogStore.getState().active?.key === 'environmentSheet') dialog.environmentSheet.close();
    };
  }, [baseUrl, dialog, deploymentId, hasDeployments, props.sheet, url]);
  const withProp = <K extends 'options' | 'resourceOptions'>(prop: K, open: (value: NonNullable<EnvironmentPage[K]>) => void) =>
    router.reload({
      only: [prop],
      onSuccess: (page) => {
        const value = (page.props as unknown as EnvironmentPage)[prop];
        if (value) open(value);
      },
      onNetworkError: () => {
        toast.error('Unable to load the form. Check your connection and try again.');
        return false;
      },
    });
  const create = {
    app: () => withProp('options', (options) => dialog.addComponent.open({ application, environment, options, type: 'app' })),
    loadBalancer: () => withProp('options', (options) => dialog.addComponent.open({ application, environment, options, type: 'load_balancer' })),
    database: () => withProp('resourceOptions', (options) => dialog.attachResource.open({ application, environment, options, kind: 'database' })),
    redis: () => withProp('resourceOptions', (options) => dialog.attachResource.open({ application, environment, options, kind: 'redis' })),
  };

  return (
    <ApplicationLayout>
      <Head title={`${environment.name} · ${application.name}`} />
      <div className="relative min-h-[36rem] flex-1 overflow-hidden">
        <div role="region" aria-label="Environment graph" className={cn('absolute inset-0', (component || resource) && 'lg:right-[42rem]')}>
          <Canvas topology={topology} selected={component ? `component-${component.id}` : resource ? `resource-${resource.id}` : undefined} />
        </div>

        <div
          className={cn(
            'pointer-events-none absolute inset-x-4 top-4 flex items-start justify-between gap-4',
            (component || resource) && 'lg:right-[43rem]',
          )}
        >
          <div className="pointer-events-auto flex max-w-sm flex-col items-start gap-2">
            {warnings.map((warning, index) => (
              <div key={`${index}-${warning}`} className={chip}>
                <TriangleAlertIcon className="text-warning size-4 shrink-0" />
                {warning}
              </div>
            ))}
            {lastDeployment && (
              <Link
                href={`${baseUrl}/deployments/${lastDeployment.id}${selection}`}
                preserveState
                preserveScroll
                only={panelProps}
                className={cn(chip, 'hover:bg-muted')}
              >
                <RocketIcon className="text-muted-foreground size-4 shrink-0" />
                <Status status={lastDeployment.status} color={lastDeployment.status_color} className="font-medium" />
                <span className="text-muted-foreground font-mono text-xs">{lastDeployment.commit_id?.slice(0, 7) ?? 'Resolving'}</span>
                <span className="text-muted-foreground text-xs">
                  <DateTime date={lastDeployment.created_at} relative />
                </span>
              </Link>
            )}
          </div>
          <nav
            aria-label="Environment"
            className="bg-background pointer-events-auto flex shrink-0 items-center gap-1 rounded-lg border p-1 shadow-xs"
          >
            {sheets.map((item) => (
              <Tooltip key={item.path}>
                <TooltipTrigger asChild>
                  <Button variant="ghost" size="sm" asChild>
                    <Link
                      href={`${baseUrl}/${item.path}${selection}`}
                      preserveState
                      preserveScroll
                      only={panelProps}
                      aria-current={props.sheet === item.path ? 'page' : undefined}
                    >
                      <item.icon />
                      <span className={cn('sr-only', !(component || resource) && 'md:not-sr-only')}>{item.label}</span>
                    </Link>
                  </Button>
                </TooltipTrigger>
                <TooltipContent aria-hidden="true">{item.label}</TooltipContent>
              </Tooltip>
            ))}
            {(can.createComponent || can.update) && topology.nodes.length > 0 && <CreateMenu disabled={!ready} create={create} can={can} />}
            <DeployButton application={application} environment={environment} />
          </nav>
        </div>

        {environment.last_error && (
          <div className={cn('pointer-events-none absolute inset-x-14 bottom-4 flex justify-center', (component || resource) && 'lg:right-[43rem]')}>
            <div role="alert" className={cn(chip, 'text-destructive pointer-events-auto max-w-xl')}>
              <TriangleAlertIcon className="size-4 shrink-0" />
              {environment.last_error}
            </div>
          </div>
        )}

        {topology.nodes.length === 0 && (
          <div className="pointer-events-none absolute inset-0 flex items-center justify-center p-4">
            <div className="bg-background pointer-events-auto flex max-w-md flex-col items-center gap-4 rounded-2xl border p-8 text-center shadow-lg">
              <span className="bg-muted flex size-12 items-center justify-center rounded-xl">
                <LayersIcon className="size-6" />
              </span>
              <div className="flex flex-col gap-1">
                <h2 className="text-lg font-semibold">Add your first component</h2>
                <p className="text-muted-foreground text-sm">A component runs {application.name} on one of your servers. Add one, then deploy.</p>
              </div>
              {can.createComponent ? (
                <Button disabled={!ready} onClick={create.app}>
                  <PlusIcon />
                  Add component
                </Button>
              ) : (
                <p className="text-muted-foreground text-sm">You don't have permission to add components.</p>
              )}
              <DocsLink page="components" />
            </div>
          </div>
        )}

        {component && props.domainOptions && (
          <ComponentPanel
            application={application}
            environment={environment}
            component={component}
            domains={props.componentDomains}
            scripts={props.componentScripts}
            inheritedScripts={props.inheritedScripts}
            workers={props.workers}
            cronjobs={props.cronjobs}
            targets={props.backendTargets}
            primaryCandidates={props.primaryCandidates}
            deploymentActive={props.deploymentActive}
            capabilities={props.capabilities}
            domainOptions={props.domainOptions}
            can={can}
            onClose={close}
          />
        )}
        {resource && (
          <ResourcePanel application={application} environment={environment} resource={resource} editable={can.update && ready} onClose={close} />
        )}
      </div>
    </ApplicationLayout>
  );
}

function CreateMenu({
  disabled,
  create,
  can,
}: {
  disabled: boolean;
  create: Record<'app' | 'loadBalancer' | 'database' | 'redis', () => void>;
  can: EnvironmentPage['can'];
}) {
  return (
    <DropdownMenu modal={false}>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" size="sm" disabled={disabled}>
          <PlusIcon />
          Create
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-60">
        {can.createComponent && (
          <>
            <DropdownMenuLabel className="text-muted-foreground text-xs font-normal">Components</DropdownMenuLabel>
            <DropdownMenuItem onSelect={create.app}>
              <BoxIcon />
              App
            </DropdownMenuItem>
            <DropdownMenuItem onSelect={create.loadBalancer}>
              <NetworkIcon />
              Load balancer
            </DropdownMenuItem>
          </>
        )}
        {can.createComponent && can.update && <DropdownMenuSeparator />}
        {can.update && (
          <>
            <DropdownMenuLabel className="text-muted-foreground text-xs font-normal">Resources</DropdownMenuLabel>
            <DropdownMenuItem onSelect={create.database}>
              <DatabaseIcon />
              Database
            </DropdownMenuItem>
            <DropdownMenuItem onSelect={create.redis}>
              <ZapIcon />
              Redis / Valkey
            </DropdownMenuItem>
          </>
        )}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
