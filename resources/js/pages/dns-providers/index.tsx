import SettingsLayout from '@/layouts/settings/layout';
import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import ConnectDNSProvider from '@/pages/dns-providers/components/connect-dns-provider';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/dns-providers/components/columns';
import { DNSProvider } from '@/types/dns-provider';
import { PaginatedData, SharedData } from '@/types';
import { BookOpenIcon } from 'lucide-react';

type Page = {
  dnsProviders: PaginatedData<DNSProvider>;
};

export default function DNSProviders() {
  const page = usePage<Page & SharedData>();

  return (
    <SettingsLayout>
      <Head title="DNS Providers" />
      <Container className="max-w-5xl">
        <div className="flex items-start justify-between">
          <Heading title="DNS Providers" description="Here you can manage all of the DNS provider connections" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/settings/dns-providers" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <ConnectDNSProvider>
              <Button>Connect</Button>
            </ConnectDNSProvider>
          </div>
        </div>

        <DataTable columns={columns} paginatedData={page.props.dnsProviders} />
      </Container>
    </SettingsLayout>
  );
}
