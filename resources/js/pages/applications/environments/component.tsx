import { useState } from 'react';
import ErrorIndicator from '@/components/error-indicator';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { VitoTable } from '@/components/vito-table';
import InputError from '@/components/ui/input-error';
import { PlusIcon, RotateCwIcon, SettingsIcon, Trash2Icon } from 'lucide-react';
import EnvironmentLayout from '@/layouts/environment/layout';
import { useDialog } from '@/hooks/use-dialog';
import { Application, Component, ComponentOptions, Environment, PrimaryCandidate } from '@/types/application';
import { Worker } from '@/types/worker';
import { CronJob } from '@/types/cronjob';
import { InertiaTableData } from '@forjedio/inertia-table-react';
import { Actions as WorkerActions } from '@/pages/workers/components/columns';
import { Actions as CronJobActions } from '@/pages/cronjobs/components/columns';

export default function ShowComponent() {
  const { application, environment, component, primaryCandidates, options, workers, cronjobs, can } = usePage<{
    application: Application;
    environment: Environment;
    component: Component;
    primaryCandidates: PrimaryCandidate[];
    options: ComponentOptions;
    workers: InertiaTableData;
    cronjobs: InertiaTableData;
    can: { update: boolean; delete: boolean };
  }>().props;
  const dialog = useDialog();
  const retry = useForm({});
  const [tab, setTab] = useState('overview');
  const baseUrl = `/applications/${application.id}/environments/${environment.id}/components/${component.id}`;
  const ready = component.status === 'ready' && application.status === 'ready' && environment.status === 'ready';
  const owner = { user: component.user, url: baseUrl };
  const processTabs = [
    { surface: 'workers', label: 'Workers' },
    { surface: 'cron_jobs', label: 'Cron jobs' },
  ].filter((tab) => component.surfaces.includes(tab.surface));

  return (
    <EnvironmentLayout>
      <Head title={`${component.name} - ${environment.name}`} />
      <Container className="max-w-5xl">
        <HeaderContainer>
          <div className="flex items-center gap-3">
            <Heading title={component.name} description={environment.name} />
            <Badge variant={component.status_color}>{component.status}</Badge>
          </div>
          <div className="flex flex-wrap items-center gap-2">
            {can.update && ready && <Button variant="outline" onClick={() => dialog.componentCapabilities.open({ application, environment, component, options })}><SettingsIcon />Capabilities</Button>}
            {can.update && component.status === 'installation_failed' && application.status === 'ready' && environment.status === 'ready' && <Button variant="outline" disabled={retry.processing} onClick={() => retry.post(`${baseUrl}/retry`)}><RotateCwIcon />Retry installation</Button>}
            {can.delete && component.status !== 'deleting' && <Button variant="destructive" onClick={() => dialog.deleteComponent.open({ application, environment, component, primaryCandidates })}><Trash2Icon />{component.status === 'deletion_failed' ? 'Retry deletion' : 'Delete'}</Button>}
          </div>
        </HeaderContainer>
        <InputError message={(retry.errors as Record<string, string>).component} />
        {component.last_error && <p role="alert" className="text-destructive text-sm">{component.last_error}</p>}
        {component.needs_redeploy && <p className="text-muted-foreground text-sm">Configuration changes require a new deployment.</p>}
        <Tabs value={processTabs.some((processTab) => processTab.surface === tab) ? tab : 'overview'} onValueChange={setTab}>
          <TabsList>
            <TabsTrigger value="overview">Overview</TabsTrigger>
            {processTabs.map((tab) => <TabsTrigger key={tab.surface} value={tab.surface}>{tab.label}</TabsTrigger>)}
          </TabsList>
          <TabsContent value="overview">
            <Card>
              <CardContent className="flex flex-col gap-4 p-4">
                <dl className="grid gap-4 sm:grid-cols-3">
                  <div><dt className="text-muted-foreground text-sm">Server</dt><dd><Link className="hover:underline" href={`/servers/${component.server_id}`}>{component.server_name}</Link></dd></div>
                  <div><dt className="text-muted-foreground text-sm">Linux user</dt><dd>{component.user}</dd></div>
                  <div><dt className="text-muted-foreground text-sm">Type</dt><dd>{component.type}</dd></div>
                  <div className="sm:col-span-3"><dt className="text-muted-foreground text-sm">Path</dt><dd className="break-all font-mono text-sm">{component.path}</dd></div>
                  <div className="sm:col-span-3"><dt className="text-muted-foreground text-sm">Capabilities</dt><dd className="flex flex-wrap gap-2">{component.capability_configs.length ? component.capability_configs.map((capability) => <Badge key={capability.capability} variant={capability.status_color}>{capability.capability}: {capability.status}</Badge>) : 'None'}</dd></div>
                </dl>
                <div className="flex flex-col gap-2">
                  <h2 className="font-medium">Deploy public key</h2>
                  {component.ssh_key ? <pre className="bg-muted overflow-x-auto rounded-md p-3 text-sm">{component.ssh_key}</pre> : <p className="text-muted-foreground text-sm">No deploy key. GitHub App sources do not need one; other sources create it during installation.</p>}
                </div>
              </CardContent>
            </Card>
          </TabsContent>
          {component.surfaces.includes('workers') && <TabsContent value="workers" className="flex flex-col gap-4">
            <HeaderContainer>
              <Heading title="Workers" description="Processes run from the current release. They remain stopped until the first deployment. Managed processes are changed through their capability." />
              {can.update && ready && component.capabilities.includes('worker') && <Button onClick={() => dialog.workerForm.open({ serverId: component.server_id, component: owner })}><PlusIcon />Create worker</Button>}
            </HeaderContainer>
            <VitoTable
              tableData={workers}
              cellRenderers={{
                status: ({ row }) => {
                  const worker = row.worker as Worker;
                  return <div className="flex items-center gap-1.5"><Badge variant={worker.status_color}>{worker.status}</Badge><ErrorIndicator error={worker.error} label={`Worker "${worker.name}" error`} /></div>;
                },
              }}
              actions={(row) => <WorkerActions worker={row.worker as Worker} canManage={can.update && ready} />}
            />
          </TabsContent>}
          {component.surfaces.includes('cron_jobs') && <TabsContent value="cron_jobs" className="flex flex-col gap-4">
            <HeaderContainer>
              <Heading title="Cron jobs" description="Commands run as the component user in its current release. Before the first deployment they do nothing." />
              {can.update && ready && <Button onClick={() => dialog.cronjobForm.open({ serverId: component.server_id, component: owner })}><PlusIcon />Create cron job</Button>}
            </HeaderContainer>
            <VitoTable tableData={cronjobs} actions={(row) => <CronJobActions cronJob={row.cronjob as CronJob} canManage={can.update && ready} />} />
          </TabsContent>}
        </Tabs>
      </Container>
    </EnvironmentLayout>
  );
}
