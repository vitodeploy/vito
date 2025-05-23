import { AppSidebar } from '@/components/app-sidebar';
import { AppHeader } from '@/components/app-header';
import { type BreadcrumbItem, NavItem, SharedData } from '@/types';
import { CSSProperties, type PropsWithChildren } from 'react';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import { usePage } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { toast } from 'sonner';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';

export default function Layout({
  children,
  secondNavItems,
  secondNavTitle,
}: PropsWithChildren<{
  breadcrumbs?: BreadcrumbItem[];
  secondNavItems?: NavItem[];
  secondNavTitle?: string;
}>) {
  const page = usePage<SharedData>();

  if (page.props.flash && page.props.flash.success) toast.success(page.props.flash.success);
  if (page.props.flash && page.props.flash.error) toast.error(page.props.flash.error);
  if (page.props.flash && page.props.flash.info) toast.info(page.props.flash.info);
  if (page.props.flash && page.props.flash.warning) toast.error(page.props.flash.warning);

  const queryClient = new QueryClient();

  return (
    <QueryClientProvider client={queryClient}>
      <SidebarProvider
        style={
          {
            '--sidebar-width': '300px',
          } as CSSProperties
        }
        defaultOpen={!!(secondNavItems && secondNavItems.length > 0)}
      >
        <AppSidebar secondNavItems={secondNavItems} secondNavTitle={secondNavTitle} />
        <SidebarInset>
          <AppHeader />
          <div className="flex flex-1 flex-col">{children}</div>
          <Toaster richColors />
        </SidebarInset>
      </SidebarProvider>
    </QueryClientProvider>
  );
}
