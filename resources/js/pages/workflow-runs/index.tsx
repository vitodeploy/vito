import { Head, router, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { DynamicTable } from '@/components/dynamic-table';
import { DynamicTableData } from '@/types/dynamic-table';
import { BreadcrumbItem } from '@/types';
import Layout from '@/layouts/app/layout';
import { Workflow } from '@/types/workflow';

export default function Workflows() {
  const page = usePage<{
    workflow: Workflow;
    workflowRuns: DynamicTableData;
  }>();

  const breadcrumbs: BreadcrumbItem[] = [
    {
      title: 'Workflows',
      href: route('workflows'),
    },
    {
      title: page.props.workflow.name,
      href: route('workflows.show', { workflow: page.props.workflow.id }),
    },
  ];

  return (
    <Layout breadcrumbs={breadcrumbs}>
      <Head title={`History of ${page.props.workflow.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title={`History of ${page.props.workflow.name}`} description="Here you can see a list of executions" />
          <div className="flex items-center gap-2"></div>
        </HeaderContainer>

        <DynamicTable
          tableData={page.props.workflowRuns}
          onRowClick={(row) => router.visit(route('workflow-runs.show', { workflow: row.workflow_id as number, workflowRun: row.id as number }))}
        />
      </Container>
    </Layout>
  );
}
