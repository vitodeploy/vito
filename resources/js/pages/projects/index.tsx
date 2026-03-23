import Container from '@/components/container';
import { DataTable } from '@/components/data-table';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import SettingsLayout from '@/layouts/settings/layout';
import { columns as invitationColumns } from '@/pages/projects/components/invitations';
import ProjectForm from '@/pages/projects/components/project-form';
import DeleteProject from '@/pages/projects/components/delete-project';
import Users from '@/pages/projects/components/users';
import LeaveProject from '@/pages/projects/components/leave-project';
import { PaginatedData } from '@/types';
import { Project } from '@/types/project';
import { Head, usePage } from '@inertiajs/react';
import { BookOpenIcon, MoreVerticalIcon, PlusIcon } from 'lucide-react';
import { ProjectUser } from '@/types/project-user';
import { DynamicTable } from '@/components/dynamic-table';
import { DynamicTableData, Row } from '@/types/dynamic-table';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';

export default function Projects() {
  const page = usePage<{
    projects: DynamicTableData;
    invitations: PaginatedData<ProjectUser>;
  }>();

  return (
    <SettingsLayout>
      <Head title="Projects" />

      <Container className="max-w-5xl">
        <div className="flex items-start justify-between">
          <Heading title="Projects" description="Here you can manage your projects" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/settings/projects" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <ProjectForm>
              <Button>
                <PlusIcon />
                Create project
              </Button>
            </ProjectForm>
          </div>
        </div>
        <DynamicTable
          tableData={page.props.projects}
          actions={(row: Row) => {
            const project = row as unknown as Project;
            return (
              <div className="flex items-center justify-end">
                <DropdownMenu modal={false}>
                  <DropdownMenuTrigger asChild>
                    <Button variant="ghost" className="h-8 w-8 p-0">
                      <span className="sr-only">Open menu</span>
                      <MoreVerticalIcon />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end">
                    <Users project={project}>
                      <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Users</DropdownMenuItem>
                    </Users>
                    <ProjectForm project={project}>
                      <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Edit</DropdownMenuItem>
                    </ProjectForm>
                    {(row.role as string) !== 'owner' && (
                      <LeaveProject project={project}>
                        <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Leave project</DropdownMenuItem>
                      </LeaveProject>
                    )}
                    {(row.role as string) === 'owner' && (
                      <>
                        <DropdownMenuSeparator />
                        <DeleteProject project={project}>
                          <DropdownMenuItem onSelect={(e) => e.preventDefault()} variant="destructive">
                            Delete Project
                          </DropdownMenuItem>
                        </DeleteProject>
                      </>
                    )}
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            );
          }}
        />

        {page.props.invitations.data.length > 0 && (
          <>
            <Heading title="Invitations" description="Here you can see the projects you're invited to" />
            <DataTable columns={invitationColumns} paginatedData={page.props.invitations} />
          </>
        )}
      </Container>
    </SettingsLayout>
  );
}
