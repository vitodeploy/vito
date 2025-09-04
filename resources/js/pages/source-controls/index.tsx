import SettingsLayout from '@/layouts/settings/layout';
import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import ConnectSourceControl from '@/pages/source-controls/components/connect-source-control';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/source-controls/components/columns';
import { SourceControl } from '@/types/source-control';
import { Configs, PaginatedData } from '@/types';
import { BookOpenIcon } from 'lucide-react';

type Page = {
  sourceControls: PaginatedData<SourceControl>;
  configs: Configs;
};

export default function SourceControls() {
  const page = usePage<Page>();

  return (
    <SettingsLayout>
      <Head title="Source Controls" />
      <Container className="max-w-5xl">
        <div className="flex items-start justify-between">
          <Heading title="Source Controls" description="Here you can manage all of the source control connections" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/settings/source-controls" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <ConnectSourceControl>
              <Button>Connect</Button>
            </ConnectSourceControl>
          </div>
        </div>
        <DataTable columns={columns} paginatedData={page.props.sourceControls} />
      </Container>
    </SettingsLayout>
  );
}
