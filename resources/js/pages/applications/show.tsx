import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import { Application } from '@/types/application';
import { Deployment } from '@/types/deployment';
import { ServerLog } from '@/types/server-log';
import { PaginatedData, SharedData } from '@/types';
import ServerLayout from '@/layouts/server/layout';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, LoaderCircleIcon, PencilIcon, RocketIcon } from 'lucide-react';
import { FormEvent, useCallback, useState } from 'react';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import FormSuccessful from '@/components/form-successful';
import { DataTable } from '@/components/data-table';
import { columns as logColumns } from '@/pages/server-logs/components/columns';
import { useRealtime, useSocketListener } from '@/hooks/use-socket-events';
import DateTime from '@/components/date-time';
import { DynamicFieldConfig } from '@/types/dynamic-field-config';
import DynamicField from '@/components/ui/dynamic-field';
import { Form, FormFields } from '@/components/ui/form';

type Page = {
  server: Server;
  application: Application;
  logs: PaginatedData<ServerLog>;
  deployments: PaginatedData<Deployment>;
} & SharedData;

function InstallingView({ application, server, logs }: { application: Application; server: Server; logs: PaginatedData<ServerLog> }) {
  const [logsData] = useRealtime<ServerLog>(logs, 'server-log');

  return (
    <ServerLayout>
      <Head title={`Installing ${application.domain} - ${server.name}`} />
      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Installing application" description="Your application is being installed. Here you can see the logs." />
          <Badge variant={application.status_color}>{application.status}</Badge>
        </HeaderContainer>
        <DataTable columns={logColumns} paginatedData={logsData} />
      </Container>
    </ServerLayout>
  );
}

function DeployDialog({ application, server }: { application: Application; server: Server }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.post(route('applications.deploy', { server: server.id, application: application.id }), {
      onSuccess: () => setOpen(false),
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button>
          <RocketIcon />
          <span className="hidden lg:block">Deploy</span>
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Deploy</DialogTitle>
          <DialogDescription className="sr-only">Deploy application</DialogDescription>
        </DialogHeader>
        <div className="space-y-2 p-4">
          <p>Are you sure you want to deploy this application?</p>
        </div>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Deploy
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function EditDialog({ application, server, editForm }: { application: Application; server: Server; editForm: DynamicFieldConfig[] }) {
  const [open, setOpen] = useState(false);

  const initialData: Record<string, unknown> = {};
  editForm.forEach((field) => {
    initialData[field.name] = application.type_data[field.name] ?? field.default ?? '';
  });

  const form = useForm(initialData);

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.put(route('applications.update', { server: server.id, application: application.id }), {
      onSuccess: () => setOpen(false),
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="outline">
          <PencilIcon />
          <span className="hidden lg:block">Edit</span>
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Edit Application</DialogTitle>
          <DialogDescription>Update the configuration and redeploy.</DialogDescription>
        </DialogHeader>
        <Form id="edit-application-form" className="p-4" onSubmit={submit}>
          <FormFields>
            {editForm.map((config) => (
              <DynamicField
                key={`edit-field-${config.name}`}
                value={form.data[config.name] as string | boolean}
                onChange={(value) => form.setData(config.name, value)}
                config={config}
                error={form.errors[config.name as keyof typeof form.errors] as string}
              />
            ))}
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button form="edit-application-form" type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Save & Deploy
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

const deploymentColumns = [
  {
    accessorKey: 'id',
    header: 'ID',
  },
  {
    accessorKey: 'created_at',
    header: 'Deployed At',
    enableSorting: true,
    cell: ({ row }: { row: { original: Deployment } }) => {
      return <DateTime date={row.original.created_at} />;
    },
  },
  {
    accessorKey: 'status',
    header: 'Status',
    cell: ({ row }: { row: { original: Deployment } }) => {
      return <Badge variant={row.original.status_color}>{row.original.status}</Badge>;
    },
  },
];

function DeploymentsTable({ deployments }: { deployments: PaginatedData<Deployment> }) {
  const [deploymentsData] = useRealtime<Deployment>(deployments, 'deployment');

  return <DataTable columns={deploymentColumns} paginatedData={deploymentsData} />;
}

export default function ApplicationShow() {
  const page = usePage<Page>();
  const { application, server, logs, deployments } = page.props;
  const typeConfig = page.props.configs.application.types[application.type];
  const editForm = typeConfig?.edit_form || [];

  useSocketListener(
    useCallback(
      (event) => {
        if (event.type === 'application.updated' && (event.data as unknown as Application).id === application.id) {
          router.reload();
        }
      },
      [application.id],
    ),
  );

  if (application.status !== 'ready') {
    return <InstallingView application={application} server={server} logs={logs} />;
  }

  return (
    <ServerLayout>
      <Head title={`${application.domain} - ${server.name}`} />
      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Overview" description={`${typeConfig?.label || application.type} \u2014 ${application.domain}`} />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            {editForm.length > 0 && <EditDialog application={application} server={server} editForm={editForm} />}
            <DeployDialog application={application} server={server} />
          </div>
        </HeaderContainer>

        <DeploymentsTable deployments={deployments} />
      </Container>
    </ServerLayout>
  );
}
