import { Head, usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/data-table';
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

  return (
    <Layout>
      <Head title={`DNS Records - ${page.props.domain.domain}`} />
      <Container className="max-w-5xl">
        <div className="flex items-start justify-between">
          <Heading title={`DNS Records for ${page.props.domain.domain}`} description="Manage DNS records for this domain" />
          <div className="flex items-center gap-2">
            <SyncRecords domain={page.props.domain} />
            <Button onClick={() => dialog.dnsRecordForm.open({ domain: page.props.domain })}>
              <PlusIcon />
              Add Record
            </Button>
          </div>
        </div>
        <DataTable columns={columns} data={page.props.records} />
      </Container>
    </Layout>
  );
}
