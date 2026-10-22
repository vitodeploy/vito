import { useRef } from 'react';
import { Head, Link, router, usePage, usePoll } from '@inertiajs/react';
import { ReloadOptions } from '@inertiajs/core';
import { toast } from 'sonner';
import { LayersIcon, PlusIcon } from 'lucide-react';
import Container from '@/components/container';
import { Button } from '@/components/ui/button';
import { useDialog } from '@/hooks/use-dialog';
import { useSocketListener } from '@/hooks/use-socket-events';
import Layout from '@/layouts/app/layout';
import { EmptyState } from '@/pages/applications/components/section';
import Status from '@/pages/applications/components/status';
import { Application } from '@/types/application';

export default function ShowApplication() {
  const { application, can } = usePage<{
    application: Application;
    can: { delete: boolean; createEnvironment: boolean };
  }>().props;
  const dialog = useDialog();
  const leaving = useRef(false);
  const leave = () => {
    if (leaving.current) return;
    leaving.current = true;
    router.visit('/applications', {
      onFinish: () => {
        leaving.current = false;
      },
      onNetworkError: () => {
        toast.error('Unable to open Applications. Check your connection and try again.');
        return false;
      },
    });
  };
  const reloadOptions: ReloadOptions = {
    only: ['application', 'can'],
    onBefore: () => !leaving.current,
    onHttpException: (response) => {
      if (response.status === 404) {
        leave();
        return false;
      }
    },
  };
  usePoll(10000, reloadOptions);
  useSocketListener((event) => {
    if (leaving.current) return;
    if (event.type === 'application.deleted' && event.data?.id === application.id) {
      leave();
    } else if ((event.type === 'application.updated' && event.data?.id === application.id)
      || (event.type?.startsWith('environment.') && event.data?.application_id === application.id)) {
      router.reload(reloadOptions);
    }
  });

  return (
    <Layout>
      <Head title={application.name} />
      <Container className="flex max-w-xl flex-col gap-6 py-16">
        <div className="text-muted-foreground flex items-center gap-2 text-sm">
          <Link href="/applications" className="hover:text-foreground">Applications</Link>
          <span>/</span>
          <span className="text-foreground font-medium">{application.name}</span>
          {application.status !== 'ready' && <Status status={application.status} color={application.status_color} className="text-xs" />}
        </div>
        <EmptyState
          icon={LayersIcon}
          title="Create an environment"
          description={`Environments deploy a branch of ${application.repository}, such as production or staging.`}
          action={can.createEnvironment && application.status === 'ready' && <Button onClick={() => dialog.createEnvironment.open({ application })}><PlusIcon />New environment</Button>}
        />
        {can.delete && application.status !== 'deleting' && (
          <Button variant="link" className="text-destructive self-center" onClick={() => dialog.deleteApplicationResource.open({ application })}>
            {application.status === 'deletion_failed' ? 'Retry deleting this application' : 'Delete this application'}
          </Button>
        )}
      </Container>
    </Layout>
  );
}
