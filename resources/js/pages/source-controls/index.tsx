import SettingsLayout from '@/layouts/settings/layout';
import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import ConnectSourceControl from '@/pages/source-controls/components/connect-source-control';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/source-controls/components/columns';
import { SourceControl } from '@/types/source-control';
import { PaginatedData, SharedData } from '@/types';
import { BookOpenIcon, GithubIcon } from 'lucide-react';

type Page = SharedData & {
  sourceControls: PaginatedData<SourceControl>;
};

export default function SourceControls() {
  const page = usePage<Page>();
  const githubAppInstalled = page.props.configs.github_app.installed;

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
            {githubAppInstalled && (
              <a href={route('github-app.install')} title="Install GitHub App on an organization">
                <Button variant="outline" size="icon">
                  <GithubIcon />
                  <span className="sr-only">Install GitHub App on an organization</span>
                </Button>
              </a>
            )}
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
