import { useCallback, useEffect, useRef } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import ServerLayout from '@/layouts/server/layout';
import SiteBanners from '@/components/site-banners';
import { Server } from '@/types/server';
import { Site } from '@/types/site';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { TriangleAlertIcon } from 'lucide-react';
import { useConfigs } from '@/stores/bootstrap-store';
import { SiteToolingProps, SiteToolingStatus } from '@/types/site-tooling';
import ToolingTable from '@/pages/site-tooling/components/tooling-table';
import { useRealtimeRecord, useSocketListener, type SocketEventData } from '@/hooks/use-socket-events';
import { toast } from 'sonner';
import { ToolingDescriptor } from '@/types';

export default function SiteTooling() {
  const page = usePage<{ server: Server; site: Site } & SiteToolingProps>();
  const site = useRealtimeRecord<Site>(page.props.site, 'site')!;

  const configs = useConfigs();
  const tools = configs?.tooling ?? [];
  const { isolated_user, sibling_sites, installed_versions, tool_statuses } = page.props;

  useStatusTransitionToasts(tool_statuses, tools);

  const currentIsolatedUserId = site.isolated_user_id;
  const ownSubmitAt = useRef<number>(0);

  useSocketListener(
    useCallback(
      (event: SocketEventData) => {
        if (event.type !== 'isolated-user.tooling-updated') return;
        const data = event.data as { id?: number } | null | undefined;
        if (!data || !currentIsolatedUserId || data.id !== currentIsolatedUserId) return;

        // The action we just submitted broadcasts immediately after flipping
        // the status — the POST/DELETE response already delivers those props,
        // so swallow the echo to avoid a redundant reload. Completion
        // broadcasts arrive later (after the SSH job finishes) and slip
        // through this window.
        if (Date.now() - ownSubmitAt.current < 2000) {
          ownSubmitAt.current = 0;
          return;
        }

        router.reload({
          only: ['installed_versions', 'tool_statuses', 'sibling_sites', 'watch_site_ids'],
        });
      },
      [currentIsolatedUserId],
    ),
  );

  return (
    <ServerLayout>
      <Head title={`Tooling - ${site.domain}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Tooling" description={`Manage developer tooling installed for isolated user ${isolated_user}.`} />
        </HeaderContainer>

        <SiteBanners site={site} />

        {sibling_sites.length > 0 && (
          <Alert>
            <TriangleAlertIcon className="text-warning!" />
            <AlertDescription className="block">
              <p>
                Changing settings will affect all sites sharing the same isolated user (<strong>{isolated_user}</strong>). Changes could break other
                sites.
              </p>
              <div className="mt-3 flex flex-wrap items-center gap-2">
                <Badge variant="success">{site.domain}</Badge>
                {sibling_sites.map((sibling) => (
                  <Badge key={sibling.id} asChild variant="gray">
                    <Link href={sibling.url}>{sibling.domain}</Link>
                  </Badge>
                ))}
              </div>
            </AlertDescription>
          </Alert>
        )}

        <ToolingTable
          site={site}
          tools={tools}
          installedVersions={installed_versions}
          statuses={tool_statuses}
          siblingCount={sibling_sites.length}
          onSubmit={() => {
            ownSubmitAt.current = Date.now();
          }}
        />
      </Container>
    </ServerLayout>
  );
}

function useStatusTransitionToasts(statuses: Record<string, SiteToolingStatus>, tools: ToolingDescriptor[]): void {
  const prevRef = useRef<Record<string, SiteToolingStatus>>(statuses);

  useEffect(() => {
    const prev = prevRef.current;
    for (const tool of tools) {
      const before = prev[tool.id] ?? null;
      const after = statuses[tool.id] ?? null;
      if (before === after) continue;

      if (before === 'installing' && after === null) {
        toast.success(`${tool.label} installed.`);
      } else if (before === 'uninstalling' && after === null) {
        toast.success(`${tool.label} uninstalled.`);
      } else if (after === 'install_failed') {
        toast.error(`Failed to install ${tool.label}. Check the site logs.`);
      } else if (after === 'uninstall_failed') {
        toast.error(`Failed to uninstall ${tool.label}. Check the site logs.`);
      }
    }
    prevRef.current = statuses;
  }, [statuses, tools]);
}
