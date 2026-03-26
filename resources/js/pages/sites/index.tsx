import { Head, Link, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import ServerLayout from '@/layouts/server/layout';
import Layout from '@/layouts/app/layout';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { BookOpenIcon, EyeIcon, PlusIcon, TriangleAlertIcon } from 'lucide-react';
import { VitoTable } from '@/components/vito-table';
import CreateSite from '@/pages/sites/components/create-site';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import type { InertiaTableData, Row } from 'inertia-table-react';

type Page = {
  server?: Server;
  sites: InertiaTableData;
};

export default function Sites() {
  const page = usePage<Page>();

  const Comp = page.props.server ? ServerLayout : Layout;

  return (
    <Comp>
      <Head title={`Sites ${page.props.server ? ' - ' + page.props.server.name : ''}`} />
      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Sites" description="Here you can manage websites" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/sites/site-types" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <CreateSite server={page.props.server}>
              <Button>
                <PlusIcon />
                <span className="hidden lg:block">Create site</span>
              </Button>
            </CreateSite>
          </div>
        </HeaderContainer>

        <VitoTable
          tableData={page.props.sites}
          actions={(row: Row) => {
            const warnings = (row.warnings as Array<{ key: string }>) ?? [];
            const count = warnings.length;
            return (
              <div className="flex items-center justify-end gap-2">
                {count > 0 && (
                  <TooltipProvider delayDuration={0}>
                    <Tooltip>
                      <TooltipTrigger asChild>
                        <div className="bg-warning/15 text-warning border-warning/40 flex cursor-default items-center gap-1.5 rounded-md border px-2 py-1.5">
                          <TriangleAlertIcon className="h-4 w-4" />
                          <span className="text-xs font-semibold">{count}</span>
                        </div>
                      </TooltipTrigger>
                      <TooltipContent>
                        {count} {count === 1 ? 'warning' : 'warnings'}
                      </TooltipContent>
                    </Tooltip>
                  </TooltipProvider>
                )}
                <Link href={route('application', { server: row.server_id, site: row.id })} prefetch>
                  <Button variant="outline" size="sm">
                    <EyeIcon />
                  </Button>
                </Link>
              </div>
            );
          }}
        />
      </Container>
    </Comp>
  );
}
