import SettingsLayout from '@/layouts/settings/layout';
import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import ConnectStorageProvider from '@/pages/storage-providers/components/connect-storage-provider';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/storage-providers/components/columns';
import { StorageProvider } from '@/types/storage-provider';
import { PaginatedData } from '@/types';
import { BookOpenIcon } from 'lucide-react';

type Page = {
  storageProviders: PaginatedData<StorageProvider>;
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

        <DataTable columns={columns} paginatedData={page.props.storageProviders} />
      </Container>
    </SettingsLayout>
  );
}
