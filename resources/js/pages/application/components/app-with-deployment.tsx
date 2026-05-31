import { Head, usePage } from '@inertiajs/react';
import { Site } from '@/types/site';
import ServerLayout from '@/layouts/server/layout';
import { Server } from '@/types/server';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { BookOpenIcon, MoreHorizontalIcon, MoreVerticalIcon, RocketIcon } from 'lucide-react';
import { Deployment } from '@/types/deployment';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import DeploymentScript from '@/pages/application/components/deployment-script';
import Env from '@/pages/application/components/env';
import Deploy from '@/pages/application/components/deploy';
import { VitoTable } from '@/components/vito-table';
import { Download, View } from '@/pages/server-logs/components/columns';
import AutoDeployment from '@/pages/application/components/auto-deployment';
import { DeploymentScript as DeploymentScriptType } from '@/types/deployment-script';
import { useRealtimeRecord } from '@/hooks/use-socket-events';
import type { CellRenderProps, InertiaTableData, Row } from '@forjedio/inertia-table-react';
import { asRow } from '@/lib/inertia-table';
import { useDialog } from '@/hooks/use-dialog';

import SiteBanners from '@/components/site-banners';
import ProxiedAppCard from '@/pages/application/components/proxied-app-card';
import { Worker } from '@/types/worker';

const commitCell = ({ row }: CellRenderProps) => {
  const commit = (row.commit_data ?? {}) as Deployment['commit_data'];
  if (!commit.message) {
    return <span className="text-muted-foreground">No message</span>;
  }
  const href = commit.url && /^https?:\/\//.test(commit.url) ? commit.url : undefined;
  return href ? (
    <a href={href} target="_blank" rel="noopener noreferrer" className="text-primary inline-flex truncate font-mono">
      <span className="block max-w-[200px] overflow-x-hidden overflow-ellipsis">{commit.message}</span>
    </a>
  ) : (
    <span className="inline-flex truncate font-mono">
      <span className="block max-w-[200px] overflow-x-hidden overflow-ellipsis">{commit.message}</span>
    </span>
  );
};

const releaseCell = ({ row }: CellRenderProps) => (
  <div className="inline-flex items-center gap-2">
    {(row.release as string | null) ?? ''}
    {(row.active as boolean) && <Badge variant="default">active</Badge>}
  </div>
);

export default function AppWithDeployment() {
  const page = usePage<{
    server: Server;
    site: Site;
    deployments: InertiaTableData;
    deploymentScript: DeploymentScriptType;
    buildScript?: DeploymentScriptType;
    preFlightScript?: DeploymentScriptType;
    worker: Worker | null;
  }>();
  const dialog = useDialog();

  const site = useRealtimeRecord<Site>(page.props.site, 'site')!;

  return (
    <ServerLayout>
      <Head title={`${site.domain} - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Application" description="Here you can manage the deployed application" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/sites/application" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <Deploy site={site}>
              <Button>
                <RocketIcon />
                <span className="hidden lg:block">Deploy</span>
              </Button>
            </Deploy>
            <DropdownMenu modal={false}>
              <DropdownMenuTrigger asChild>
                <Button variant="outline" className="p-0">
                  <span className="sr-only">Open menu</span>
                  <MoreHorizontalIcon />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <AutoDeployment site={site}>
                  <DropdownMenuItem onSelect={(e) => e.preventDefault()} disabled={!site.source_control_id}>
                    {site.auto_deploy ? 'Disable' : 'Enable'} auto deploy
                  </DropdownMenuItem>
                </AutoDeployment>
                {!site.modern_deployment && (
                  <DeploymentScript site={site} script={page.props.deploymentScript} description="This script will be executed on every deployment.">
                    <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Deployment Script</DropdownMenuItem>
                  </DeploymentScript>
                )}
                {page.props.buildScript && site.modern_deployment && (
                  <DeploymentScript
                    site={site}
                    script={page.props.buildScript}
                    description="This script will build resources like composer and npm before release"
                  >
                    <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Build Script</DropdownMenuItem>
                  </DeploymentScript>
                )}
                {page.props.preFlightScript && site.modern_deployment && (
                  <DeploymentScript
                    site={site}
                    script={page.props.preFlightScript}
                    description="This script will be executed before release like migrations and optimizations"
                  >
                    <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Pre Flight Script</DropdownMenuItem>
                  </DeploymentScript>
                )}
                <Env site={site}>
                  <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Update .env</DropdownMenuItem>
                </Env>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </HeaderContainer>

        <SiteBanners site={site} />

        {site.is_proxied_site_type && (
          <>
            <ProxiedAppCard site={site} initialWorker={page.props.worker} />
            <Heading title="Deployments" description="History of past deployments. The active release is what's currently serving traffic." />
          </>
        )}

        <VitoTable
          tableData={page.props.deployments}
          cellRenderers={{ commit: commitCell, release: releaseCell }}
          actions={(row: Row) => {
            const deployment = asRow<Deployment>(row, ['id', 'site_id', 'server_id']);
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
                    {deployment.log && (
                      <>
                        <View serverLog={deployment.log} />
                        <Download serverLog={deployment.log}>
                          <DropdownMenuItem>Download</DropdownMenuItem>
                        </Download>
                      </>
                    )}
                    {!deployment.active && deployment.release && deployment.status === 'finished' && (
                      <DropdownMenuItem
                        variant="destructive"
                        onSelect={() =>
                          dialog.confirm.open({
                            title: 'Rollback',
                            description: `Are you sure you want to rollback your site to version [${deployment.release}]?`,
                            confirmLabel: 'Rollback',
                            method: 'post',
                            url: route('application.rollback', {
                              server: deployment.server_id,
                              site: deployment.site_id,
                              deployment: deployment.id,
                            }),
                          })
                        }
                      >
                        Rollback
                      </DropdownMenuItem>
                    )}
                    <DropdownMenuItem
                      variant="destructive"
                      onSelect={() =>
                        dialog.confirm.open({
                          title: `Delete release [${deployment.release || deployment.id}]`,
                          description: `Are you sure you want to delete release ${deployment.release || deployment.id}? This will delete the release files from the server. This action cannot be undone.`,
                          variant: 'destructive',
                          confirmLabel: 'Delete',
                          method: 'delete',
                          url: route('application.deployments.destroy', {
                            server: deployment.server_id,
                            site: deployment.site_id,
                            deployment: deployment.id,
                          }),
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
    </ServerLayout>
  );
}
