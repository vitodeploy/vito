import { NavUser } from '@/components/nav-user';
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupContent,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { type NavItem, SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
  BookOpen,
  ChevronRightIcon,
  CogIcon,
  Folder,
  Globe,
  ListEndIcon,
  LogsIcon,
  MousePointerClickIcon,
  ServerIcon,
  Settings2Icon,
  WorkflowIcon,
  ZapIcon,
} from 'lucide-react';
import AppLogo from './app-logo';
import { Icon } from '@/components/icon';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';

export function AppSidebar({ secondNavItems, secondNavTitle }: { secondNavItems?: NavItem[]; secondNavTitle?: string }) {
  const page = usePage<SharedData>();

  const mainNavItems: NavItem[] = [
    {
      title: 'Servers',
      href: route('servers'),
      icon: ServerIcon,
    },
    {
      title: 'Sites',
      href: route('sites.all'),
      icon: MousePointerClickIcon,
    },
    {
      title: 'Scripts',
      href: route('scripts'),
      icon: ZapIcon,
    },
    {
      title: 'Workflows',
      href: route('workflows'),
      icon: WorkflowIcon,
    },
    {
      title: 'Domains',
      href: route('domains'),
      icon: Globe,
    },
    {
      title: 'Settings',
      href: route('settings'),
      icon: CogIcon,
    },
    {
      title: 'Admin',
      href: route('admin'),
      icon: Settings2Icon,
      hidden: !page.props.auth.user?.is_admin,
    },
  ];

  const footerNavItems: NavItem[] = [
    {
      title: 'Horizon Dashboard',
      href: route('horizon.index'),
      icon: ListEndIcon,
      hidden: !page.props.auth.user?.is_admin,
    },
    {
      title: 'Vito Logs',
      href: route('log-viewer.index'),
      icon: LogsIcon,
      hidden: !page.props.auth.user?.is_admin,
    },
    {
      title: 'Repository',
      href: 'https://github.com/vitodeploy/vito',
      icon: Folder,
    },
    {
      title: 'Documentation',
      href: 'https://vitodeploy.com',
      icon: BookOpen,
    },
  ];

  return (
    <Sidebar collapsible="icon" className="overflow-hidden [&>[data-sidebar=sidebar]]:flex-row">
      {/* This is the first sidebar */}
      {/* We disable collapsible and adjust width to icon. */}
      {/* This will make the sidebar appear as icons. */}
      <Sidebar collapsible="none" className="h-auto !w-[calc(var(--sidebar-width-icon)_+_1px)] border-r">
        <SidebarHeader>
          <SidebarMenu>
            <SidebarMenuItem>
              <SidebarMenuButton size="lg" asChild className="md:h-8 md:p-0">
                <Link href={route('servers')} prefetch>
                  <Tooltip>
                    <TooltipTrigger>
                      <AppLogo />
                    </TooltipTrigger>
                    <TooltipContent side="right">{page.props.version}</TooltipContent>
                  </Tooltip>
                </Link>
              </SidebarMenuButton>
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarHeader>
        <SidebarContent>
          <SidebarGroup>
            <SidebarGroupContent className="md:px-0">
              <SidebarMenu>
                {mainNavItems.map((item) => (
                  <SidebarMenuItem key={`${item.title}-${item.href}`}>
                    <SidebarMenuButton
                      asChild
                      isActive={item.onlyActivePath ? window.location.href === item.href : window.location.href.startsWith(item.href)}
                      tooltip={{ children: item.title, hidden: false }}
                      hidden={item.hidden}
                    >
                      {item.external ? (
                        <a href={item.href} target="_blank">
                          {item.icon && <item.icon />}
                          <span>{item.title}</span>
                        </a>
                      ) : (
                        <Link href={item.href}>
                          {item.icon && <item.icon />}
                          <span>{item.title}</span>
                        </Link>
                      )}
                    </SidebarMenuButton>
                  </SidebarMenuItem>
                ))}
              </SidebarMenu>
            </SidebarGroupContent>
          </SidebarGroup>
        </SidebarContent>
        <SidebarFooter className="hidden md:flex">
          <SidebarMenu>
            {footerNavItems.map((item) => (
              <SidebarMenuItem key={`${item.title}-${item.href}`} hidden={item.hidden}>
                <SidebarMenuButton asChild tooltip={{ children: item.title, hidden: false }}>
                  <a href={item.href} target="_blank" rel="noopener noreferrer">
                    {item.icon && <Icon iconNode={item.icon} />}
                    <span className="sr-only">{item.title}</span>
                  </a>
                </SidebarMenuButton>
              </SidebarMenuItem>
            ))}
          </SidebarMenu>
          <NavUser />
        </SidebarFooter>
      </Sidebar>

      {/* This is the second sidebar */}
      {/* We enable collapsible and adjust width to icon. */}
      {/* This will make the sidebar appear as icons. */}
      {secondNavItems && secondNavItems.length > 0 && (
        <Sidebar collapsible="none" className="flex flex-1">
          <SidebarHeader className="hidden h-12 border-b p-0 md:flex">
            <div className="flex h-full items-center p-2">
              <span className="max-w-[200px] truncate overflow-ellipsis">{secondNavTitle}</span>
            </div>
          </SidebarHeader>
          <SidebarContent>
            <SidebarGroup>
              <SidebarGroupContent>
                <SidebarMenu>
                  {secondNavItems.map((item) => {
                    const isActive = item.onlyActivePath ? window.location.href === item.href : window.location.href.startsWith(item.href);

                    if (item.children && item.children.length > 0) {
                      return (
                        <Collapsible key={`${item.title}-${item.href}`} defaultOpen={isActive} className="group/collapsible">
                          <SidebarMenuItem>
                            <CollapsibleTrigger asChild>
                              <SidebarMenuButton disabled={item.isDisabled || false} hidden={item.hidden}>
                                {item.icon && <item.icon />}
                                <span>{item.title}</span>
                                <ChevronRightIcon className="ml-auto transition-transform group-data-[state=open]/collapsible:rotate-90" />
                              </SidebarMenuButton>
                            </CollapsibleTrigger>
                            <CollapsibleContent>
                              <SidebarMenuSub>
                                {item.children.map((childItem) => (
                                  <SidebarMenuSubItem key={`${childItem.title}-${childItem.href}`} hidden={childItem.hidden}>
                                    <SidebarMenuButton
                                      asChild
                                      isActive={
                                        childItem.onlyActivePath
                                          ? window.location.href === childItem.href
                                          : window.location.href.startsWith(childItem.href)
                                      }
                                    >
                                      {childItem.external ? (
                                        <a href={childItem.href} target="_blank">
                                          {childItem.icon && <childItem.icon />}
                                          <span>{childItem.title}</span>
                                        </a>
                                      ) : (
                                        <Link href={childItem.href}>
                                          {childItem.icon && <childItem.icon />}
                                          <span>{childItem.title}</span>
                                        </Link>
                                      )}
                                    </SidebarMenuButton>
                                  </SidebarMenuSubItem>
                                ))}
                              </SidebarMenuSub>
                            </CollapsibleContent>
                          </SidebarMenuItem>
                        </Collapsible>
                      );
                    }

                    return (
                      <SidebarMenuItem key={`${item.title}-${item.href}`} hidden={item.hidden}>
                        <SidebarMenuButton isActive={isActive} asChild>
                          {item.external ? (
                            <a href={item.href} target="_blank">
                              {item.icon && <item.icon />}
                              <span>{item.title}</span>
                            </a>
                          ) : (
                            <Link
                              href={item.isDisabled ? '#' : item.href}
                              disabled={item.isDisabled || false}
                              className={item.isDisabled ? 'pointer-events-none opacity-50' : ''}
                            >
                              {item.icon && <item.icon />}
                              <span>{item.title}</span>
                            </Link>
                          )}
                        </SidebarMenuButton>
                      </SidebarMenuItem>
                    );
                  })}
                </SidebarMenu>
              </SidebarGroupContent>
            </SidebarGroup>
          </SidebarContent>
        </Sidebar>
      )}
    </Sidebar>
  );
}
