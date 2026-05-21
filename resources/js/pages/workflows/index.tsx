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
import Run from '@/pages/workflows/components/run';
import DeleteWorkflow from '@/pages/workflows/components/delete-workflow';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import type { InertiaTableData, Row } from 'inertia-table-react';
import { asRow } from '@/lib/inertia-table';

export default function Workflows() {
  const page = usePage<{
    workflows: InertiaTableData;
  }>();

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
            const workflow = asRow<Workflow>(row, ['id', 'name']);
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
                    <Run workflow={workflow}>
                      <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Run</DropdownMenuItem>
                    </Run>
                    <DropdownMenuItem onSelect={() => router.visit(route('workflow-runs', { workflow: workflow.id }))}>History</DropdownMenuItem>
                    <DropdownMenuItem onSelect={() => router.visit(route('workflows.show', { workflow: workflow.id }))}>Edit</DropdownMenuItem>
                    <DropdownMenuSeparator />
                    <DeleteWorkflow workflow={workflow}>
                      <DropdownMenuItem onSelect={(e) => e.preventDefault()} className="text-destructive focus:text-destructive">
                        Delete
                      </DropdownMenuItem>
                    </DeleteWorkflow>
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
