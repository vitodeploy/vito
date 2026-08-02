import SettingsLayout from '@/layouts/settings/layout';
import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import ConnectDNSProvider from '@/pages/dns-providers/components/connect-dns-provider';
import { VitoTable } from '@/components/vito-table';
import { DNSProvider } from '@/types/dns-provider';
import { SharedData } from '@/types';
import { BookOpenIcon, MoreVerticalIcon } from 'lucide-react';
import { DropdownMenu, DropdownMenuContent, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import Edit from '@/pages/dns-providers/components/edit';
import Delete from '@/pages/dns-providers/components/delete';
import type { InertiaTableData, Row } from '@forjedio/inertia-table-react';
import { asRow } from '@/lib/inertia-table';

type Page = {
  dnsProviders: InertiaTableData;
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

        <VitoTable
          tableData={page.props.dnsProviders}
          actions={(row: Row) => {
            const dnsProvider = asRow<DNSProvider>(row, ['id', 'name', 'global', 'provider', 'editable_data']);
            return (
              <div className="flex items-center justify-end">
                <DropdownMenu modal={false}>
                  <DropdownMenuTrigger asChild>
                    <Button variant="ghost" className="h-8 w-8 p-0">
                      <span className="sr-only">Open menu</span>
                      <MoreVerticalIcon />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end">
                    <Edit dnsProvider={dnsProvider} />
                    <DropdownMenuSeparator />
                    <Delete dnsProvider={dnsProvider} />
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            );
          }}
        />
      </Container>
    </SettingsLayout>
  );
}
