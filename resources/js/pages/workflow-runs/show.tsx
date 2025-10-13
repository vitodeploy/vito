import Container from '@/components/container';
import Heading from '@/components/heading';
import Layout from '@/layouts/app/layout';
import { Head, usePage } from '@inertiajs/react';
import Logs from './components/logs';
import { WorkflowRun } from '@/types/workflow-run';
import { Badge } from '@/components/ui/badge';
import { Workflow } from '@/types/workflow';
import { BreadcrumbItem } from '@/types';

export default function Show() {
  const page = usePage<{
    workflow: Workflow;
    workflowRun: WorkflowRun;
  }>();

  const breadcrumbs: BreadcrumbItem[] = [
    {
      title: 'Workflows',
      href: route('workflows'),
    },
    {
      title: `History [${page.props.workflow.name}]`,
      href: route('workflow-runs', { workflow: page.props.workflow.id }),
    },
    {
      title: 'Logs',
      href: route('workflow-runs', { workflow: page.props.workflow.id }),
    },
  ];

  return (
    <Layout breadcrumbs={breadcrumbs}>
      <Head title={`Workflow [${page.props.workflow.name}]`} />
      <Container className="max-w-5xl">
        <div className="flex items-start justify-between">
          <Heading title={`Workflow [${page.props.workflow.name}]`} description="Here you can see the result of your workflow's execution" />
          <Badge variant={page.props.workflowRun.status_color}>{page.props.workflowRun.status}</Badge>
        </div>

        <Logs workflowRun={page.props.workflowRun} />
      </Container>
    </Layout>
  );
}
