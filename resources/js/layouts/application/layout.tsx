import { ReactNode, useCallback, useEffect, useRef } from 'react';
import { router, usePage, usePoll } from '@inertiajs/react';
import { ReloadOptions } from '@inertiajs/core';
import { toast } from 'sonner';
import { useSocketListener } from '@/hooks/use-socket-events';
import { Application, Component, Environment } from '@/types/application';
import Layout from '@/layouts/app/layout';

const reloadProps = [
  'application',
  'environment',
  'environments',
  'applicationCan',
  'topology',
  'lastDeployment',
  'warnings',
  'primaryCandidates',
  'capabilities',
  'domainOptions',
  'component',
  'componentDomains',
  'componentScripts',
  'inheritedScripts',
  'workers',
  'cronjobs',
  'backendTargets',
  'resource',
  'deploymentActive',
  'can',
  'sheet',
  'deployments',
  'deployment',
  'variables',
  'scripts',
  'sourceControls',
];

export default function ApplicationLayout({ children }: { children: ReactNode }) {
  const { application, environment, component } = usePage<{
    application: Application;
    environment: Environment;
    component?: Component | null;
  }>().props;
  const baseUrl = `/applications/${application.id}/environments/${environment.id}`;
  const leaving = useRef(false);
  const navigating = useRef(0);
  useEffect(() => {
    const before = router.on('before', ({ detail: { visit } }) => {
      if (!visit.async && visit.method === 'get' && visit.url.href !== window.location.href) {
        router.cancelAll({ async: true, prefetch: false, sync: false });
      }
    });
    const start = router.on('start', ({ detail: { visit } }) => {
      if (!visit.async) navigating.current += 1;
    });
    const finish = router.on('finish', ({ detail: { visit } }) => {
      if (!visit.async) navigating.current = Math.max(0, navigating.current - 1);
    });
    return () => {
      before();
      start();
      finish();
    };
  }, []);
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
    onBefore: () => !leaving.current && navigating.current === 0,
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
    } else if (
      ['environment-domain.updated', 'environment-deployment.updated'].includes(event.type) &&
      event.data?.environment_id === environment.id
    ) {
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

  return <Layout>{children}</Layout>;
}
