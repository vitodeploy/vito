import { Head, usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { BreadcrumbHeader } from '@/components/breadcrumb-header';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/data-table';
import { BreadcrumbItem } from '@/types';
import { DNSRecord } from '@/types/dns-record';
import { Domain } from '@/types/domain';
import { PlusIcon } from 'lucide-react';
import Layout from '@/layouts/app/layout';
import { getColumns } from './components/record-columns';
import SyncRecords from './components/sync-records';
import { useConfigs } from '@/stores/bootstrap-store';
import { useDialog } from '@/hooks/use-dialog';

type Page = {
  domain: Domain;
  records: DNSRecord[];
};

export default function DomainShow() {
  const page = usePage<Page>();
  const configs = useConfigs()!;
  const dialog = useDialog();

  const domain = page.props.domain;
  const providerKey = domain.dns_provider?.provider;
  const providerConfig = providerKey ? configs.dns_provider?.providers?.[providerKey] : undefined;
  const columns = useMemo(() => getColumns(providerConfig, domain), [providerKey, domain.id]);

  const breadcrumbs: BreadcrumbItem[] = [
    {
      title: 'Domains',
      href: route('domains'),
    },
    {
      title: domain.domain,
      href: route('domains.show', { domain: domain.id }),
    },
  ];

  return (
    <Layout>
      <Head title={`DNS Records - ${page.props.domain.domain}`} />
      <Container className="max-w-5xl">
        <HeaderContainer>
          <BreadcrumbHeader breadcrumbs={breadcrumbs}>
            <Heading title={`DNS Records for ${page.props.domain.domain}`} description="Manage DNS records for this domain" />
          </BreadcrumbHeader>
          <div className="flex items-center gap-2">
            <SyncRecords domain={page.props.domain} />
            <Button onClick={() => dialog.dnsRecordForm.open({ domain: page.props.domain })}>
              <PlusIcon />
              Add Record
            </Button>
          </div>
        </HeaderContainer>
        <DataTable columns={columns} data={page.props.records} />
      </Container>
    </Layout>
  );
}
