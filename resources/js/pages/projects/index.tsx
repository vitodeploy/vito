import SettingsLayout from '@/layouts/settings/layout';
import { Head, usePage } from '@inertiajs/react';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/projects/components/columns';
import { Project } from '@/types/project';
import Container from '@/components/container';
import Heading from '@/components/heading';
import React from 'react';
import CreateProject from '@/pages/projects/components/create-project';
import { Button } from '@/components/ui/button';

export default function Projects() {
  const page = usePage<{
    projects: {
      data: Project[];
    };
  }>();

  return (
    <SettingsLayout>
      <Head title="Projects" />

      <Container className="max-w-3xl">
        <div className="flex items-start justify-between">
          <Heading title="Projects" description="Here you can manage your projects" />
          <div className="flex items-center gap-2">
            <CreateProject>
              <Button variant="outline">Create project</Button>
            </CreateProject>
          </div>
        </div>
        <DataTable columns={columns} data={page.props.projects.data} />
      </Container>
    </SettingsLayout>
  );
}
