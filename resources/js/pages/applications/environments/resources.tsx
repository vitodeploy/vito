import { Head, usePage } from '@inertiajs/react';
import { InertiaTableData, Row } from '@forjedio/inertia-table-react';
import { asRow } from '@/lib/inertia-table';
import { PlusIcon, RefreshCwIcon, UnplugIcon } from 'lucide-react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import DocsLink from '@/pages/applications/components/docs-link';
import { VitoTable } from '@/components/vito-table';
import { useDialog } from '@/hooks/use-dialog';
import EnvironmentLayout from '@/layouts/environment/layout';
import { Application, Environment, EnvironmentAttachment, EnvironmentResourceOptions } from '@/types/application';

export default function Resources() {
  const { application, environment, attachments, resourceOptions, canUpdate } = usePage<{
    application: Application;
    environment: Environment;
    attachments: InertiaTableData;
    resourceOptions: EnvironmentResourceOptions;
    canUpdate: boolean;
  }>().props;
  const dialog = useDialog();
  const editable = canUpdate && application.status === 'ready' && environment.status === 'ready';
  const baseUrl = `/applications/${application.id}/environments/${environment.id}/resources`;

  return (
    <EnvironmentLayout>
      <Head title={`Resources - ${environment.name}`} />
      <Container className="max-w-5xl">
        <HeaderContainer>
          <div className="flex items-center gap-3"><Heading title="Resources" description="Managed access for app components. Connection variables apply on the next deploy." /><Badge variant="warning">Beta</Badge></div>
          {editable && <Button onClick={() => dialog.attachResource.open({ application, environment, options: resourceOptions })}><PlusIcon />Attach resource</Button>}
        </HeaderContainer>
        <VitoTable tableData={attachments} actions={(row) => {
          const { attachment: payload } = asRow<{ attachment: Row }>(row, ['attachment']);
          const attachment = asRow<EnvironmentAttachment>(payload, ['id', 'status']);
          return editable && <div className="flex justify-end gap-2">
            {['failed', 'deletion_failed'].includes(attachment.status) && <Button variant="outline" size="sm" aria-label={`Retry ${attachment.name ?? 'resource'}`} onClick={() => dialog.confirm.open({
              title: 'Retry resource access', description: 'Retry the failed synchronization or cleanup without deleting stored data.', confirmLabel: 'Retry', method: 'post', url: `${baseUrl}/${attachment.id}/retry`,
            })}><RefreshCwIcon /></Button>}
            {!['deleting', 'deletion_failed'].includes(attachment.status) && <Button variant="outline" size="sm" aria-label={`Detach ${attachment.name ?? 'resource'}`} onClick={() => dialog.confirm.open({
              title: `Detach ${attachment.name ?? 'resource'}`, description: 'Managed users and firewall rules will be removed. The database, stored data, other users and service networking setting are kept. Apps using these credentials will lose access; redeploy to remove the connection variables.', variant: 'destructive', confirmLabel: 'Detach', method: 'delete', url: `${baseUrl}/${attachment.id}`,
            })}><UnplugIcon /></Button>}
          </div>;
        }} />
        {attachments.data.length === 0 && <DocsLink page="resources" />}
      </Container>
    </EnvironmentLayout>
  );
}
