import { AppSidebar } from '@/components/app-sidebar';
import { AppHeader } from '@/components/app-header';
import { type BreadcrumbItem, NavItem, SharedData } from '@/types';
import { type PropsWithChildren, useEffect } from 'react';
import { SidebarInset, SidebarProvider } from '@/components/ui/sidebar';
import { usePage } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { toast } from 'sonner';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { CheckCircle2Icon, CircleXIcon, InfoIcon, TriangleAlertIcon } from 'lucide-react';
import { TerminalProvider, useTerminal } from '@/contexts/terminal-context';
import FloatingTerminal from '@/components/floating-terminal';
import { TooltipProvider } from '@/components/ui/tooltip';

function GlobalTerminal() {
  const { state, updateServer } = useTerminal();

  // Get server from current page (if available)
  const page = usePage<SharedData>();
  const server = page.props.server;

  // Update server in context when server changes, but only if terminal is open
  useEffect(() => {
    if (server && state.isOpen) {
      updateServer(server);
    }
  }, [server, updateServer, state.isOpen]);

  // Only render if terminal is open and we have a server in context
  if (!state.isOpen || !state.currentServer) {
    return null;
  }

  // Use the server from context (which persists across page changes)
  return <FloatingTerminal server={state.currentServer} />;
}

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

  useEffect(() => {
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
  }, [page.props.flash]);

  const queryClient = new QueryClient();

  return (
    <QueryClientProvider client={queryClient}>
      <TerminalProvider>
        <TooltipProvider>
          <SidebarProvider defaultOpen={!!(secondNavItems && secondNavItems.length > 0)}>
            <AppSidebar secondNavItems={secondNavItems} secondNavTitle={secondNavTitle} />
            <SidebarInset>
              <AppHeader />
              <div className="flex flex-1 flex-col">{children}</div>
              <Toaster richColors position="bottom-center" />
            </SidebarInset>
          </SidebarProvider>
          <GlobalTerminal />
        </TooltipProvider>
      </TerminalProvider>
    </QueryClientProvider>
  );
}
