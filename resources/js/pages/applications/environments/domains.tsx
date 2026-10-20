import { Head, usePage } from '@inertiajs/react';
import { PencilIcon, PlusIcon, RefreshCwIcon, TrashIcon } from 'lucide-react';
import { InertiaTableData } from '@forjedio/inertia-table-react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import DocsLink from '@/pages/applications/components/docs-link';
import { VitoTable } from '@/components/vito-table';
import { useDialog } from '@/hooks/use-dialog';
import EnvironmentLayout from '@/layouts/environment/layout';
import { Application, Environment, EnvironmentDomain, EnvironmentDomainOptions } from '@/types/application';

export default function Domains() {
  const { application, environment, domains, domainOptions, canUpdate } = usePage<{
    application: Application;
    environment: Environment;
    domains: InertiaTableData;
    domainOptions: EnvironmentDomainOptions;
    canUpdate: boolean;
  }>().props;
  const dialog = useDialog();
  const editable = canUpdate && application.status === 'ready' && environment.status === 'ready';
  const baseUrl = `/applications/${application.id}/environments/${environment.id}/domains`;

  return (
    <EnvironmentLayout>
      <Head title={`Domains - ${environment.name}`} />
      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Domains" description="Route verified hosts to a web component in this environment." badges={<Badge variant="warning">Beta</Badge>} />
          {editable && <Button onClick={() => dialog.environmentDomainForm.open({ application, environment, options: domainOptions })}><PlusIcon />Add domain</Button>}
        </HeaderContainer>
        <VitoTable tableData={domains} actions={(row) => {
          const domain = row.domain as unknown as EnvironmentDomain;
          return editable && <div className="flex justify-end gap-2">
            {domain.status === 'pending' && <Button variant="outline" size="sm" aria-label={`Verify ${domain.host}`} onClick={() => dialog.confirm.open({
              title: `Verify ${domain.host}`, description: 'Confirm the DNS records point to the target server. Verification will run in the background.',
              confirmLabel: 'Verify now', method: 'post', url: `${baseUrl}/${domain.id}/verify`,
            })}><RefreshCwIcon /></Button>}
            <Button variant="outline" size="sm" disabled={domain.status === 'updating'} aria-label={`Edit ${domain.host}`} onClick={() => dialog.environmentDomainForm.open({ application, environment, options: domainOptions, domain })}><PencilIcon /></Button>
            <Button variant="outline" size="sm" aria-label={`Delete ${domain.host}`} onClick={() => dialog.confirm.open({
              title: `Delete ${domain.host}`, description: 'The vhost and any unused environment certificate will be removed. Redirects using an alias must be deleted first.',
              variant: 'destructive', confirmLabel: 'Delete', method: 'delete', url: `${baseUrl}/${domain.id}`,
            })}><TrashIcon /></Button>
          </div>;
        }} />
        {domains.data.length === 0 && <DocsLink page="domains" />}
      </Container>
    </EnvironmentLayout>
  );
}
