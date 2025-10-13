import { AppSidebar } from '@/components/app-sidebar';
import { AppHeader } from '@/components/app-header';
import { type BreadcrumbItem, NavItem, SharedData } from '@/types';
import { type PropsWithChildren, useEffect } from 'react';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import { usePage } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { toast } from 'sonner';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { TooltipProvider } from '@/components/ui/tooltip';
import { Breadcrumbs } from '@/components/breadcrumbs';

export default function Layout({
  children,
  secondNavItems,
  secondNavTitle,
  breadcrumbs,
}: PropsWithChildren<{
  breadcrumbs?: BreadcrumbItem[];
  secondNavItems?: NavItem[];
  secondNavTitle?: string;
}>) {
  const page = usePage<SharedData>();

  useEffect(() => {
    if (page.props.flash && page.props.flash.success) {
      toast.success(<div className="flex items-center gap-2">{page.props.flash.success}</div>);
    }
    if (page.props.flash && page.props.flash.error) {
      toast.error(<div className="flex items-center gap-2">{page.props.flash.error}</div>);
    }
    if (page.props.flash && page.props.flash.warning) {
      toast.warning(<div className="flex items-center gap-2">{page.props.flash.warning}</div>);
    }
    if (page.props.flash && page.props.flash.info) {
      toast.info(<div className="flex items-center gap-2">{page.props.flash.info}</div>);
    }
  }, [page.props.flash]);

  const queryClient = new QueryClient();

  return (
    <QueryClientProvider client={queryClient}>
      <TooltipProvider>
        <SidebarProvider defaultOpen={!!(secondNavItems && secondNavItems.length > 0)}>
          <AppSidebar secondNavItems={secondNavItems} secondNavTitle={secondNavTitle} />
          <SidebarInset>
            <AppHeader />
            {breadcrumbs && breadcrumbs.length > 1 && (
              <div className="border-sidebar-border/70 flex w-full border-b">
                <div className="mx-auto flex h-12 w-full items-center justify-start px-4 text-neutral-500">
                  <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>
              </div>
            )}
            <div className="flex flex-1 flex-col">{children}</div>
            <Toaster richColors position="bottom-center" />
          </SidebarInset>
        </SidebarProvider>
      </TooltipProvider>
    </QueryClientProvider>
  );
}
