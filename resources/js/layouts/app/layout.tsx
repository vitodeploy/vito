import { AppSidebar } from '@/components/app-sidebar';
import { AppHeader } from '@/components/app-header';
import { type BreadcrumbItem, NavItem, SharedData } from '@/types';
import { CSSProperties, type PropsWithChildren } from 'react';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import { usePage, usePoll } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { toast } from 'sonner';

export default function Layout({
  children,
  secondNavItems,
  secondNavTitle,
}: PropsWithChildren<{
  breadcrumbs?: BreadcrumbItem[];
  secondNavItems?: NavItem[];
  secondNavTitle?: string;
}>) {
  usePoll(10000);

  const page = usePage<SharedData>();

  if (page.props.flash && page.props.flash.success) toast.success(page.props.flash.success);
  if (page.props.flash && page.props.flash.error) toast.error(page.props.flash.error);

  return (
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
  );
}
