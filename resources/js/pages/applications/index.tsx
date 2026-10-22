import { Head, Link, usePage, usePoll } from '@inertiajs/react';
import { InertiaTableData } from '@forjedio/inertia-table-react';
import { BoxesIcon, GitBranchIcon, PlusIcon } from 'lucide-react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useDialog } from '@/hooks/use-dialog';
import Layout from '@/layouts/app/layout';
import { EmptyState } from '@/pages/applications/components/section';
import Status from '@/pages/applications/components/status';
import { EnvironmentSummary } from '@/types/application';
import { SourceControl } from '@/types/source-control';

export default function Applications() {
  const { applications, sourceControls, canCreate } = usePage<{
    applications: InertiaTableData;
    sourceControls: SourceControl[];
    canCreate: boolean;
  }>().props;
  const dialog = useDialog();
  const create = () => dialog.createApplication.open({ sourceControls });
  usePoll(10000, { only: ['applications', 'canCreate'] });

  return (
    <Layout>
      <Head title="Applications" />
      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Applications" description="Deploy a repository to one or more servers, per environment." badges={<Badge variant="warning">Beta</Badge>} />
          {canCreate && applications.data.length > 0 && <Button onClick={create}><PlusIcon />New application</Button>}
        </HeaderContainer>
        {applications.data.length === 0 ? (
          <EmptyState
            icon={BoxesIcon}
            title="Deploy your first application"
            description="Pick a repository and a runtime. You'll add servers and domains from its graph."
            action={canCreate && <Button onClick={create}><PlusIcon />New application</Button>}
          />
        ) : (
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {applications.data.map((row) => {
              const environments = Array.isArray(row.environments) ? (row.environments as EnvironmentSummary[]) : [];
              return (
                <Link key={String(row.id)} href={`/applications/${row.id}`} prefetch className="bg-card hover:border-foreground/30 flex flex-col gap-4 rounded-xl border p-5 transition-colors">
                  <div className="flex items-start gap-3">
                    <span className="bg-muted flex size-10 shrink-0 items-center justify-center rounded-lg"><BoxesIcon className="size-5" /></span>
                    <div className="flex min-w-0 flex-col">
                      <span className="truncate font-semibold">{String(row.name)}</span>
                      <span className="text-muted-foreground truncate text-sm">{String(row.repository)}</span>
                    </div>
                  </div>
                  <div className="flex flex-col gap-1.5">
                    {environments.slice(0, 3).map((environment) => (
                      <div key={environment.id} className="flex items-center justify-between gap-2 text-sm">
                        <Status status={environment.status} label={environment.name} color={environment.status_color} />
                        <span className="text-muted-foreground flex items-center gap-1 truncate font-mono text-xs"><GitBranchIcon className="size-3" />{environment.branch}</span>
                      </div>
                    ))}
                    {environments.length === 0 && <span className="text-muted-foreground text-sm">No environments</span>}
                    {environments.length > 3 && <span className="text-muted-foreground text-xs">+{environments.length - 3} more</span>}
                  </div>
                  <div className="text-muted-foreground mt-auto flex items-center justify-between border-t pt-3 text-xs">
                    <span>{String(row.runtime)}</span>
                    {row.status !== 'ready' && <Status status={String(row.status)} color={String(row._status_enum_color)} className="text-xs" />}
                  </div>
                </Link>
              );
            })}
          </div>
        )}
        {(applications.links.prev || applications.links.next) && (
          <div className="flex justify-end gap-2">
            <Button variant="outline" size="sm" disabled={!applications.links.prev} asChild={!!applications.links.prev}>
              {applications.links.prev ? <Link href={applications.links.prev} preserveScroll>Previous</Link> : 'Previous'}
            </Button>
            <Button variant="outline" size="sm" disabled={!applications.links.next} asChild={!!applications.links.next}>
              {applications.links.next ? <Link href={applications.links.next} preserveScroll>Next</Link> : 'Next'}
            </Button>
          </div>
        )}
      </Container>
    </Layout>
  );
}
