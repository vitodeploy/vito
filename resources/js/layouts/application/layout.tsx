import { ReactNode, useCallback, useEffect, useRef } from 'react';
import { Link, router, usePage, usePoll } from '@inertiajs/react';
import { ReloadOptions } from '@inertiajs/core';
import { toast } from 'sonner';
import { useSocketListener } from '@/hooks/use-socket-events';
import { cn } from '@/lib/utils';
import DeployButton from '@/pages/applications/components/deploy-button';
import { Application, Component, Environment } from '@/types/application';
import Layout from '@/layouts/app/layout';

const reloadProps = [
  'application', 'environment', 'environments', 'applicationCan', 'topology', 'lastDeployment', 'warnings', 'primaryCandidates', 'capabilities',
  'domainOptions', 'component', 'componentDomains', 'componentScripts', 'inheritedScripts', 'workers', 'cronjobs',
  'backendTargets', 'resource', 'deploymentActive', 'can', 'canUpdate', 'deployments', 'deployment', 'variables', 'scripts',
];

export default function ApplicationLayout({ children }: { children: ReactNode }) {
  const { application, environment, component } = usePage<{
    application: Application;
    environment: Environment;
    component?: Component | null;
  }>().props;
  const baseUrl = `/applications/${application.id}/environments/${environment.id}`;
  const path = usePage().url.split('?')[0];
  const leaving = useRef(false);
  const leave = useCallback((url: string) => {
    if (leaving.current) return;
    leaving.current = true;
    let fallingBack = false;
    const onFinish = () => {
      leaving.current = false;
    };
    const onNetworkError = () => {
      toast.error('Unable to open the parent page. Check your connection and try again.');
      return false;
    };
    router.visit(url, {
      onNetworkError,
      onHttpException: (response) => {
        if (response.status === 404 && url !== '/applications') {
          fallingBack = true;
          router.visit('/applications', { onFinish, onNetworkError });
          return false;
        }
      },
      onFinish: () => {
        if (!fallingBack) onFinish();
      },
    });
  }, []);
  const reloadOptions: ReloadOptions = {
    only: reloadProps,
    onBefore: () => !leaving.current,
    onHttpException: (response) => {
      if (response.status === 404) {
        leave(`/applications/${application.id}`);
        return false;
      }
    },
  };
  usePoll(10000, reloadOptions);
  useEffect(() => {
    if (application.status === 'deleting') {
      leave('/applications');
    } else if (environment.status === 'deleting') {
      leave(`/applications/${application.id}`);
    }
  }, [application.id, application.status, environment.status, leave]);
  useSocketListener((event) => {
    if (leaving.current) return;
    if (event.type?.startsWith('application.') && event.data?.id === application.id) {
      if (event.type === 'application.deleted' || event.data.status === 'deleting') {
        leave('/applications');
      } else {
        router.reload(reloadOptions);
      }
    } else if (event.type?.startsWith('environment.') && event.data?.application_id === application.id) {
      if (event.data.id !== environment.id) {
        router.reload({ ...reloadOptions, only: ['environments'] });
      } else if (event.data.application_status === 'deleting') {
        leave('/applications');
      } else if (event.type === 'environment.deleted' || event.data.status === 'deleting') {
        leave(`/applications/${application.id}`);
      } else {
        router.reload(reloadOptions);
      }
    } else if (['environment-domain.updated', 'environment-deployment.updated'].includes(event.type) && event.data?.environment_id === environment.id) {
      router.reload(reloadOptions);
    } else if (event.type?.startsWith('worker.') && component && event.data?.component_id === component.id) {
      router.reload(reloadOptions);
    } else if (event.type?.startsWith('component.') && event.data?.environment_id === environment.id) {
      if (event.data.application_status === 'deleting') {
        leave('/applications');
      } else if (event.data.environment_status === 'deleting') {
        leave(`/applications/${application.id}`);
      } else if (event.type === 'component.deleted' && event.data.id === component?.id) {
        leave(baseUrl);
      } else {
        router.reload(reloadOptions);
      }
    }
  });

  const tabs = [
    { title: 'Graph', href: baseUrl, active: path === baseUrl },
    { title: 'Deployments', href: `${baseUrl}/deployments`, active: path.startsWith(`${baseUrl}/deployments`) },
    { title: 'Variables', href: `${baseUrl}/variables`, active: path.startsWith(`${baseUrl}/variables`) },
    { title: 'Settings', href: `${baseUrl}/settings`, active: path.startsWith(`${baseUrl}/settings`) },
  ];

  return (
    <Layout>
      <div className="bg-background flex h-12 shrink-0 items-center justify-between gap-4 border-b px-4">
        <nav className="-mb-px flex h-full items-stretch gap-5 overflow-x-auto" aria-label="Environment">
          {tabs.map((tab) => (
            <Link
              key={tab.title}
              href={tab.href}
              aria-current={tab.active ? 'page' : undefined}
              className={cn(
                'text-muted-foreground hover:text-foreground flex items-center border-b-2 border-transparent text-sm font-medium whitespace-nowrap transition-colors',
                tab.active && 'border-foreground text-foreground',
              )}
            >
              {tab.title}
            </Link>
          ))}
        </nav>
        <DeployButton application={application} environment={environment} />
      </div>
      {children}
    </Layout>
  );
}
