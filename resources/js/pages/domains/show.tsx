import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/data-table';
import { DNSRecord } from '@/types/dns-record';
import { Domain } from '@/types/domain';
import { PlusIcon } from 'lucide-react';
import Layout from '@/layouts/app/layout';
import RecordForm from './components/record-form';
import { columns } from './components/record-columns';

type Page = {
  domain: Domain;
  records: DNSRecord[];
};

export default function DomainShow() {
  const page = usePage<Page>();

  return (
    <Layout>
      <Head title={`DNS Records - ${page.props.domain.domain}`} />
      <Container className="max-w-5xl">
        <div className="flex items-start justify-between">
          <Heading title={`DNS Records for ${page.props.domain.domain}`} description="Manage DNS records for this domain" />
          <div className="flex items-center gap-2">
            <RecordForm domain={page.props.domain}>
              <Button>
                <PlusIcon />
                Add Record
              </Button>
            </RecordForm>
          </div>
        </div>
        <DataTable columns={columns} data={page.props.records} />
      </Container>
    </Layout>
  );
}
