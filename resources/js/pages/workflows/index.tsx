import { Head, router, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, MoreVerticalIcon, PlusIcon } from 'lucide-react';
import { VitoTable } from '@/components/vito-table';
import { Workflow } from '@/types/workflow';
import Layout from '@/layouts/app/layout';
import CreateWorkflow from './components/create-workflow';
import { useDialog } from '@/hooks/use-dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import type { InertiaTableData, Row } from '@forjedio/inertia-table-react';
import { asRow } from '@/lib/inertia-table';

export default function Workflows() {
  const page = usePage<{
    workflows: InertiaTableData;
  }>();
  const dialog = useDialog();

  return (
    <Layout>
      <Head title={`Workflows`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Workflows" description="Workflows are chained scripts that will run in the defined order" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/workflows" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <CreateWorkflow>
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">Create</span>
              </Button>
            </CreateWorkflow>
          </div>
        </HeaderContainer>

        <VitoTable
          tableData={page.props.workflows}
          actions={(row: Row) => {
            const workflow = asRow<Workflow>(row, ['id', 'name', 'run_inputs', 'run_fields']);
            return (
              <div className="flex items-center justify-end">
                <DropdownMenu modal>
                  <DropdownMenuTrigger asChild>
                    <Button variant="ghost" className="h-8 w-8 p-0">
                      <span className="sr-only">Open menu</span>
                      <MoreVerticalIcon />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end">
                    <DropdownMenuItem
                      onSelect={() =>
                        dialog.workflowRun.open({
                          workflowId: workflow.id,
                          workflowName: workflow.name,
                          inputs: workflow.run_inputs ?? {},
                          fields: workflow.run_fields,
                        })
                      }
                    >
                      Run
                    </DropdownMenuItem>
                    <DropdownMenuItem onSelect={() => router.visit(`/workflows/${workflow.id}/runs`)}>History</DropdownMenuItem>
                    <DropdownMenuItem onSelect={() => router.visit(`/workflows/${workflow.id}`)}>Edit</DropdownMenuItem>
                    <DropdownMenuItem onSelect={() => router.post(`/workflows/${workflow.id}/duplicate`)}>Duplicate</DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuItem
                      className="text-destructive focus:text-destructive"
                      onSelect={() =>
                        dialog.confirm.open({
                          title: `Delete ${workflow.name}`,
                          description: 'Are you sure you want to delete this workflow?',
                          variant: 'destructive',
                          confirmLabel: 'Delete',
                          method: 'delete',
                          url: `/workflows/${workflow.id}`,
                        })
                      }
                    >
                      Delete
                    </DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            );
          }}
        />
      </Container>
    </Layout>
  );
}
