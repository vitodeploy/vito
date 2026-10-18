import { ReactNode, useCallback, useEffect, useRef } from 'react';
import { router, usePage, usePoll } from '@inertiajs/react';
import { ReloadOptions } from '@inertiajs/core';
import { toast } from 'sonner';
import { useSocketListener } from '@/hooks/use-socket-events';
import { ArrowLeftIcon, BoxesIcon, HomeIcon, KeyRoundIcon, RefreshCwIcon } from 'lucide-react';
import Container from '@/components/container';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { NavItem } from '@/types';
import { Application, Component, Environment } from '@/types/application';
import Layout from '@/layouts/app/layout';

export default function EnvironmentLayout({ children }: { children: ReactNode }) {
  const { application, environment, component } = usePage<{ application: Application; environment: Environment; component?: Component }>().props;
  const baseUrl = `/applications/${application.id}/environments/${environment.id}`;
  const leaving = useRef(false);
  const leave = useCallback((url: string) => {
    if (leaving.current) return;
    leaving.current = true;
    let fallingBack = false;
    const onFinish = () => { leaving.current = false; };
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
      onFinish: () => { if (!fallingBack) onFinish(); },
    });
  }, []);
  const reloadOptions: ReloadOptions = {
    only: ['application', 'environment', 'components', 'component', 'primaryCandidates', 'workers', 'cronjobs', 'variables', 'warnings', 'canUpdate'],
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
    } else if (event.type?.startsWith('environment.') && event.data?.id === environment.id) {
      if (event.data.application_status === 'deleting') {
        leave('/applications');
      } else if (event.type === 'environment.deleted' || event.data.status === 'deleting') {
        leave(`/applications/${application.id}`);
      } else {
        router.reload(reloadOptions);
      }
    } else if (event.type?.startsWith('worker.') && component && event.data?.component_id === component.id) {
      router.reload(reloadOptions);
    } else if (event.type?.startsWith('component.') && event.data?.environment_id === environment.id) {
      if (event.data.application_status === 'deleting') {
        leave('/applications');
      } else if (event.data.environment_status === 'deleting') {
        leave(`/applications/${application.id}`);
      } else if (event.type === 'component.deleted' && event.data.id === component?.id) {
        leave(`${baseUrl}/components`);
      } else {
        router.reload(reloadOptions);
      }
    }
  });
  const sidebarNavItems: NavItem[] = [
    { title: application.name, href: `/applications/${application.id}`, onlyActivePath: `/applications/${application.id}`, icon: ArrowLeftIcon },
    { title: 'Overview', href: baseUrl, onlyActivePath: baseUrl, icon: HomeIcon },
    { title: 'Components', href: `${baseUrl}/components`, icon: BoxesIcon },
    { title: 'Variables', href: `${baseUrl}/variables`, icon: KeyRoundIcon },
  ];

  return (
    <Layout secondNavItems={sidebarNavItems} secondNavTitle={environment.name}>
      {environment.needs_redeploy && <Container className="max-w-5xl pb-0">
        <Alert>
          <RefreshCwIcon />
          <AlertTitle>Redeploy to apply</AlertTitle>
          <AlertDescription>This environment has changes that will take effect on the next deploy.</AlertDescription>
        </Alert>
      </Container>}
      {children}
    </Layout>
  );
}
