import { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import { InertiaTableData } from '@forjedio/inertia-table-react';
import { RocketIcon } from 'lucide-react';
import { VitoTable } from '@/components/vito-table';
import { Badge } from '@/components/ui/badge';
import { Application, Environment, EnvironmentDeployment } from '@/types/application';
import { panelProps } from './canvas';
import DeploymentPanel from './deployment-panel';
import { EmptyState } from './section';
import SidePanel from './side-panel';
import Status from './status';

export default function DeploymentsSheet({ application, environment, deployments, deployment, onClose }: {
  application: Application;
  environment: Environment;
  deployments: InertiaTableData;
  deployment: EnvironmentDeployment | null;
  onClose: () => void;
}) {
  const baseUrl = `/applications/${application.id}/environments/${environment.id}/deployments`;
  const query = usePage().url.split('?')[1];
  const url = (id?: number | string) => `${baseUrl}${id ? `/${id}` : ''}${query ? `?${query}` : ''}`;
  const visit = (id?: number | string) => router.visit(url(id), { preserveState: true, preserveScroll: true, only: panelProps });
  const [listOpen, setListOpen] = useState(!deployment);
  if (!deployment && !listOpen) setListOpen(true);

  const content = deployments.data.length === 0 ? (
    <EmptyState icon={RocketIcon} title="No deployments yet" description="Deploy to build and release the latest commit." />
  ) : (
    <VitoTable
      tableData={deployments}
      onRowClick={(row) => visit(String(row.id))}
      rowClassName={(row) => (row.id === deployment?.id ? 'bg-muted/60' : '')}
      cellRenderers={{
        status: ({ row }) => <Status status={String(row.status)} color={String(row._status_enum_color)} />,
        message: ({ row }) => (
          <span className="flex max-w-md items-center gap-2">
            <Link href={url(String(row.id))} preserveState preserveScroll only={panelProps} onClick={(event) => event.stopPropagation()} className="truncate font-medium hover:underline">
              {String(row.message)}
            </Link>
            {row.id === environment.live_run_id && <Badge variant="success">Live</Badge>}
          </span>
        ),
        commit: ({ row }) => <span className="font-mono text-xs">{String(row.commit)}</span>,
      }}
    />
  );

  return (
    <>
      {listOpen && (
        <SidePanel
          icon={RocketIcon}
          title="Deployments"
          subtitle={`Every deployment releases ${environment.branch} to all components at once.`}
          tabs={[{ value: 'deployments', label: 'Deployments', content }]}
          onClose={onClose}
          sheet
        />
      )}
      {deployment && <DeploymentPanel application={application} environment={environment} deployment={deployment} onClose={listOpen ? () => visit() : onClose} />}
    </>
  );
}
