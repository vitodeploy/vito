import Container from '@/components/container';
import { DataTable } from '@/components/data-table';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import SettingsLayout from '@/layouts/settings/layout';
import { columns as projectColumns } from '@/pages/projects/components/columns';
import { columns as invitationColumns } from '@/pages/projects/components/invitations';
import ProjectForm from '@/pages/projects/components/project-form';
import { BreadcrumbItem, PaginatedData } from '@/types';
import { Project } from '@/types/project';
import { Head, usePage } from '@inertiajs/react';
import { PlusIcon } from 'lucide-react';
import { ProjectUser } from '@/types/project-user';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Settings',
    href: '/settings',
  },
  {
    title: 'Projects',
    href: '/settings/projects',
  },
];

export default function Projects() {
  const page = usePage<{
    projects: PaginatedData<Project>;
    invitations: PaginatedData<ProjectUser>;
  }>();

  return (
    <SettingsLayout breadcrumbs={breadcrumbs}>
      <Head title="Projects" />

      <Container className="max-w-5xl">
        <div className="flex items-start justify-between">
          <Heading title="Projects" description="Here you can manage your projects" />
          <div className="flex items-center gap-2">
            <ProjectForm>
              <Button>
                <PlusIcon />
                Create project
              </Button>
            </ProjectForm>
          </div>
        </div>
        <DataTable columns={projectColumns} paginatedData={page.props.projects} />

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
