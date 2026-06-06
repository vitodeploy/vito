import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { BreadcrumbHeader } from '@/components/breadcrumb-header';
import { DataTable } from '@/components/data-table';
import { BreadcrumbItem, PaginatedData } from '@/types';
import { columns } from '@/pages/scripts/components/execution-columns';
import { Script } from '@/types/script';
import { ScriptExecution } from '@/types/script-execution';
import Layout from '@/layouts/app/layout';
import { useRealtime } from '@/hooks/use-socket-events';

type Page = {
  script: Script;
  executions: PaginatedData<ScriptExecution>;
};

export default function Show() {
  const page = usePage<Page>();
  const [executions] = useRealtime<ScriptExecution>(page.props.executions, 'script-execution', { script_id: page.props.script.id });

  const breadcrumbs: BreadcrumbItem[] = [
    {
      title: 'Scripts',
      href: route('scripts'),
    },
    {
      title: page.props.script.name,
      href: route('scripts.show', { script: page.props.script.id }),
    },
  ];

  return (
    <Layout>
      <Head title={`History of ${page.props.script.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <BreadcrumbHeader breadcrumbs={breadcrumbs}>
            <Heading title={`History of ${page.props.script.name}`} description="Here you can see the script executions" />
          </BreadcrumbHeader>
        </HeaderContainer>

        <DataTable columns={columns} paginatedData={executions} />
      </Container>
    </Layout>
  );
}
