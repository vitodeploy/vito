import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { BreadcrumbHeader } from '@/components/breadcrumb-header';
import { DataTable } from '@/components/data-table';
import { BreadcrumbItem, PaginatedData } from '@/types';
import { hookColumns } from '@/pages/scripts/components/hook-columns';
import { Script } from '@/types/script';
import { ScriptEventHook } from '@/types/script-event-hook';
import Layout from '@/layouts/app/layout';
import { Button } from '@/components/ui/button';
import { PlusIcon } from 'lucide-react';
import { useDialog } from '@/hooks/use-dialog';

type Page = {
  script: Script;
  hooks: PaginatedData<ScriptEventHook>;
};

export default function Hooks() {
  const page = usePage<Page>();
  const dialog = useDialog();

  const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Scripts', href: route('scripts') },
    { title: page.props.script.name, href: route('scripts.show', { script: page.props.script.id }) },
    { title: 'Event Hooks', href: route('scripts.hooks', { script: page.props.script.id }) },
  ];

  return (
    <Layout>
      <Head title={`Event Hooks — ${page.props.script.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <BreadcrumbHeader breadcrumbs={breadcrumbs}>
            <Heading title="Event Hooks" description="Automatically run this script when lifecycle events occur" />
          </BreadcrumbHeader>
          <Button onClick={() => dialog.scriptHookForm.open({ script: page.props.script })}>
            <PlusIcon />
            Add Hook
          </Button>
        </HeaderContainer>

        <DataTable columns={hookColumns(page.props.script)} paginatedData={page.props.hooks} />
      </Container>
    </Layout>
  );
}
