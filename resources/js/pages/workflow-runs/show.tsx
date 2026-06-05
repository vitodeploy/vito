import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { BreadcrumbHeader } from '@/components/breadcrumb-header';
import Layout from '@/layouts/app/layout';
import { Head, usePage } from '@inertiajs/react';
import Logs from './components/logs';
import { WorkflowRun } from '@/types/workflow-run';
import { Badge } from '@/components/ui/badge';
import { Workflow } from '@/types/workflow';
import { BreadcrumbItem } from '@/types';
import { useSocketListener } from '@/hooks/use-socket-events';
import { useCallback, useState } from 'react';

export default function Show() {
  const page = usePage<{
    workflow: Workflow;
    workflowRun: WorkflowRun;
  }>();

  const [workflowRun, setWorkflowRun] = useState<WorkflowRun>(page.props.workflowRun);

  // Listen for realtime status updates
  useSocketListener(
    useCallback(
      (event) => {
        if (event.type !== 'workflow-run.updated') return;
        const data = event.data as WorkflowRun | undefined;
        if (!data || data.id !== page.props.workflowRun.id) return;
        setWorkflowRun(data);
      },
      [page.props.workflowRun.id],
    ),
  );

  const breadcrumbs: BreadcrumbItem[] = [
    {
      title: 'Workflows',
      href: route('workflows'),
    },
    {
      title: `History of ${page.props.workflow.name}`,
      href: route('workflow-runs', { workflow: page.props.workflow.id }),
    },
    {
      title: 'Logs',
      href: route('workflow-runs', { workflow: page.props.workflow.id }),
    },
  ];

  return (
    <Layout>
      <Head title={`Workflow [${page.props.workflow.name}]`} />
      <Container className="max-w-5xl">
        <HeaderContainer>
          <BreadcrumbHeader breadcrumbs={breadcrumbs}>
            <Heading title={`Workflow [${page.props.workflow.name}]`} description="Here you can see the result of your workflow's execution" />
          </BreadcrumbHeader>
          <Badge variant={workflowRun.status_color}>{workflowRun.status}</Badge>
        </HeaderContainer>

        <Logs workflowRun={workflowRun} />
      </Container>
    </Layout>
  );
}
