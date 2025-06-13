import { AppSidebar } from '@/components/app-sidebar';
import { AppHeader } from '@/components/app-header';
import { type BreadcrumbItem, NavItem, SharedData } from '@/types';
import { type PropsWithChildren } from 'react';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import { usePage } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { toast } from 'sonner';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { CheckCircle2Icon, CircleXIcon, InfoIcon, TriangleAlertIcon } from 'lucide-react';

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

  if (page.props.flash && page.props.flash.success) {
    toast(
      <div className="flex items-center gap-2">
        <CheckCircle2Icon className="text-success size-5" />
        {page.props.flash.success}
      </div>,
    );
  }
  if (page.props.flash && page.props.flash.error) {
    toast(
      <div className="flex items-center gap-2">
        <CircleXIcon className="text-destructive size-5" />
        {page.props.flash.error}
      </div>,
    );
  }
  if (page.props.flash && page.props.flash.warning) {
    toast(
      <div className="flex items-center gap-2">
        <TriangleAlertIcon className="text-warning size-5" />
        {page.props.flash.warning}
      </div>,
    );
  }
  if (page.props.flash && page.props.flash.info) {
    toast(
      <div className="flex items-center gap-2">
        <InfoIcon className="text-info size-5" />
        {page.props.flash.info}
      </div>,
    );
  }

  const queryClient = new QueryClient();

  return (
    <QueryClientProvider client={queryClient}>
      <SidebarProvider defaultOpen={!!(secondNavItems && secondNavItems.length > 0)}>
        <AppSidebar secondNavItems={secondNavItems} secondNavTitle={secondNavTitle} />
        <SidebarInset>
          <AppHeader />
          <div className="flex flex-1 flex-col">{children}</div>
          <Toaster richColors position="bottom-center" />
        </SidebarInset>
      </SidebarProvider>
    </QueryClientProvider>
  );
}
