import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import { PaginatedData } from '@/types';
import ServerLayout from '@/layouts/server/layout';
import SiteBanners from '@/components/site-banners';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { BookOpenIcon, MoreVerticalIcon, PlusIcon, RefreshCwIcon, RotateCwIcon } from 'lucide-react';
import Container from '@/components/container';
import { DataTable } from '@/components/data-table';
import { Worker } from '@/types/worker';
import { columns } from '@/pages/workers/components/columns';
import { Site } from '@/types/site';
import { useRealtime } from '@/hooks/use-socket-events';
import { useDialog } from '@/hooks/use-dialog';

export default function WorkerIndex() {
  const page = usePage<{
    server: Server;
    workers: PaginatedData<Worker>;
    site?: Site;
    sites?: Array<{ id: number; domain: string }>;
  }>();
  const dialog = useDialog();

  const [workers] = useRealtime<Worker>(page.props.workers, 'worker');

  const scope = page.props.site ? { server: page.props.server.id, site: page.props.site.id } : { server: page.props.server.id };
  const scopeLabel = page.props.site ? `${page.props.site.domain}'s workers` : "this server's workers";

  return (
    <ServerLayout>
      <Head title={`Workers - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading
            title="Workers"
            description={page.props.site ? `Here you can manage ${page.props.site.domain}'s workers` : "Here you can manage server's workers"}
          />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/servers/workers" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="outline">
                  <MoreVerticalIcon />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem
                  onSelect={() =>
                    dialog.confirm.open({
                      title: 'Resync workers',
                      description: `Fetch the live status of ${scopeLabel} from the process manager and update Vito. Continue?`,
                      confirmLabel: 'Resync',
                      method: 'post',
                      url: route('workers.resync', scope),
                    })
                  }
                >
                  <RefreshCwIcon />
                  Resync
                </DropdownMenuItem>
                <DropdownMenuItem
                  onSelect={() =>
                    dialog.confirm.open({
                      title: 'Restart all workers',
                      description: `Are you sure you want to restart ${scopeLabel}?`,
                      confirmLabel: 'Restart All',
                      method: 'post',
                      url: route('workers.restart-all', scope),
                    })
                  }
                >
                  <RotateCwIcon />
                  Restart All
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
            <Button onClick={() => dialog.workerForm.open({ serverId: page.props.server.id, site: page.props.site })}>
              <PlusIcon />
              <span className="hidden lg:block">Create</span>
            </Button>
          </div>
        </HeaderContainer>

        {page.props.site && <SiteBanners site={page.props.site} />}

        <DataTable columns={columns(page.props.sites)} paginatedData={workers} />
      </Container>
    </ServerLayout>
  );
}
