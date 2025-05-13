import { AppSidebar } from '@/components/app-sidebar';
import { AppHeader } from '@/components/app-header';
import { type BreadcrumbItem, NavItem } from '@/types';
import { CSSProperties, type PropsWithChildren } from 'react';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import { usePoll } from '@inertiajs/react';

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
      </SidebarInset>
    </SidebarProvider>
  );
}
