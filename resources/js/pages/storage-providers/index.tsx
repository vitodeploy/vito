import SettingsLayout from '@/layouts/settings/layout';
import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import React from 'react';
import ConnectStorageProvider from '@/pages/storage-providers/components/connect-storage-provider';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/storage-providers/components/columns';
import { StorageProvider } from '@/types/storage-provider';

type Page = {
  storageProviders: {
    data: StorageProvider[];
  };
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
          <Heading title="Storage Providers" description="Here you can manage all of the storage provider connectinos" />
          <div className="flex items-center gap-2">
            <ConnectStorageProvider providers={page.props.configs.storage_providers}>
              <Button>Connect</Button>
            </ConnectStorageProvider>
          </div>
        </div>

        <DataTable columns={columns} data={page.props.storageProviders.data} />
      </Container>
    </SettingsLayout>
  );
}
