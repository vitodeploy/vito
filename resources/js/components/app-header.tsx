import { SidebarTrigger } from '@/components/ui/sidebar';
import { Breadcrumb, BreadcrumbItem, BreadcrumbList, BreadcrumbSeparator } from '@/components/ui/breadcrumb';
import { ProjectSwitch } from '@/components/project-switch';
import { SlashIcon } from 'lucide-react';
import { ServerSwitch } from '@/components/server-switch';

export function AppHeader() {
  return (
    <header className="bg-background -ml-1 flex h-12 shrink-0 items-center gap-2 border-b p-4 md:-ml-2">
      <SidebarTrigger className="-ml-1 md:hidden" />
      <Breadcrumb>
        <BreadcrumbList>
          <BreadcrumbItem>
            <ProjectSwitch />
          </BreadcrumbItem>
          <BreadcrumbSeparator>
            <SlashIcon />
          </BreadcrumbSeparator>
          <BreadcrumbItem>
            <ServerSwitch />
          </BreadcrumbItem>
        </BreadcrumbList>
      </Breadcrumb>
    </header>
  );
}
