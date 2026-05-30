import SettingsLayout from '@/layouts/settings/layout';
import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import ConnectStorageProvider from '@/pages/storage-providers/components/connect-storage-provider';
import { VitoTable } from '@/components/vito-table';
import { StorageProvider } from '@/types/storage-provider';
import { BookOpenIcon, MoreVerticalIcon } from 'lucide-react';
import { DropdownMenu, DropdownMenuContent, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import Edit from '@/pages/storage-providers/components/edit';
import Delete from '@/pages/storage-providers/components/delete';
import type { InertiaTableData, Row } from '@forjedio/inertia-table-react';
import { asRow } from '@/lib/inertia-table';

type Page = {
  storageProviders: InertiaTableData;
  configs: {
    storage_providers: string[];
  };
};

export default function StorageProviders() {
  const page = usePage<Page>();

  return (
    <SettingsLayout>
      <Head title="Storage Providers" />
      <Container className="max-w-5xl">
        <div className="flex items-start justify-between">
          <Heading title="Storage Providers" description="Here you can manage all of the storage provider connections" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/settings/storage-providers" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <ConnectStorageProvider>
              <Button>Connect</Button>
            </ConnectStorageProvider>
          </div>
        </div>

        <VitoTable
          tableData={page.props.storageProviders}
          actions={(row: Row) => {
            const storageProvider = asRow<StorageProvider>(row, ['id', 'name', 'global']);
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
                    <Edit storageProvider={storageProvider} />
                    <DropdownMenuSeparator />
                    <Delete storageProvider={storageProvider} />
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
