import { Head, router, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/workflow-runs/components/columns';
import { BreadcrumbItem, PaginatedData } from '@/types';
import Layout from '@/layouts/app/layout';
import { WorkflowRun } from '@/types/workflow-run';
import { Workflow } from '@/types/workflow';

export default function Workflows() {
  const page = usePage<{
    workflow: Workflow;
    workflowRuns: PaginatedData<WorkflowRun>;
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

        <DataTable
          columns={columns}
          paginatedData={page.props.workflowRuns}
          onRowClick={(row: WorkflowRun) => router.visit(route('workflow-runs.show', { workflow: row.workflow_id, workflowRun: row.id }))}
        />
      </Container>
    </Layout>
  );
}
