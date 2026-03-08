import SettingsLayout from '@/layouts/settings/layout';
import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { DataTable } from '@/components/data-table';
import { ApiKey } from '@/types/api-key';
import { getColumns } from '@/pages/api-keys/components/columns';
import CreateApiKey from '@/pages/api-keys/components/create-api-key';
import { PaginatedData } from '@/types';
import { BookOpenIcon, PlusIcon } from 'lucide-react';
import { Project } from '@/types/project';
import { useMemo } from 'react';

export default function ApiKeys() {
  const page = usePage<{
    apiKeys: PaginatedData<ApiKey>;
    projects: Project[];
  }>();

  const columns = useMemo(() => getColumns(page.props.projects), [page.props.projects]);

  return (
    <SettingsLayout>
      <Head title="API Keys" />
      <Container className="max-w-5xl">
        <div className="flex items-start justify-between">
          <Heading title="API Keys" description="Here you can manage API keys" />
          <div className="flex items-center gap-2">
            <a href="/api/docs" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                Docs
              </Button>
            </a>
            <CreateApiKey projects={page.props.projects}>
              <Button>
                <PlusIcon />
                Create
              </Button>
            </CreateApiKey>
          </div>
        </div>
        <DataTable columns={columns} paginatedData={page.props.apiKeys} />
      </Container>
    </SettingsLayout>
  );
}
