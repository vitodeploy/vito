import { Head, router, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { BreadcrumbHeader } from '@/components/breadcrumb-header';
import { VitoTable } from '@/components/vito-table';
import { BreadcrumbItem } from '@/types';
import Layout from '@/layouts/app/layout';
import { Workflow } from '@/types/workflow';
import type { InertiaTableData, Row } from '@forjedio/inertia-table-react';
import { asRow } from '@/lib/inertia-table';

export default function Workflows() {
  const page = usePage<{
    workflow: Workflow;
    workflowRuns: InertiaTableData;
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
    <Layout>
      <Head title={`History of ${page.props.workflow.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <BreadcrumbHeader breadcrumbs={breadcrumbs}>
            <Heading title={`History of ${page.props.workflow.name}`} description="Here you can see a list of executions" />
          </BreadcrumbHeader>
        </HeaderContainer>

        <VitoTable
          tableData={page.props.workflowRuns}
          onRowClick={(row: Row) => {
            const r = asRow<{ id: number; workflow_id: number }>(row, ['id', 'workflow_id']);
            router.visit(route('workflow-runs.show', { workflow: r.workflow_id, workflowRun: r.id }));
          }}
        />
      </Container>
    </Layout>
  );
}
