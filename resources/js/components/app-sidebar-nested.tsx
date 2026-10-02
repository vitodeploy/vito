import { NavUser } from '@/components/nav-user';
import { currentPath } from '@/lib/utils';
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
} from '@/components/ui/sidebar';
import { type NavItem } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';
import {
  ArrowLeftIcon,
  BellIcon,
  BookOpen,
  ChevronRightIcon,
  ClockIcon,
  CloudIcon,
  CloudUploadIcon,
  CodeIcon,
  CogIcon,
  DatabaseIcon,
  FlameIcon,
  Folder,
  HomeIcon,
  KeyIcon,
  ListIcon,
  MousePointerClickIcon,
  PlugIcon,
  RocketIcon,
  ServerIcon,
  TagIcon,
  UserIcon,
  UsersIcon,
} from 'lucide-react';
import AppLogo from './app-logo';
import { Icon } from '@/components/icon';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Server } from '@/types/server';
import { Site } from '@/types/site';

export function AppSidebar() {
  const page = usePage<{
    server?: Server;
    site?: Site;
  }>();

  const isServerMenuDisabled = !page.props.server || page.props.server.status !== 'ready';

  const mainNavItems: NavItem[] = [
    {
      title: 'Servers',
      href: '/servers',
      icon: ServerIcon,
      children: [
        {
          title: 'Overview',
          href: `/servers/${page.props.server?.id || 0}`,
          onlyActivePath: `/servers/${page.props.server?.id || 0}`,
          icon: HomeIcon,
          isDisabled: isServerMenuDisabled,
        },
        {
          title: 'Database',
          href: `/servers/${page.props.server?.id || 0}/database`,
          icon: DatabaseIcon,
          isDisabled: isServerMenuDisabled,
          children: [
            {
              title: 'Databases',
              href: `/servers/${page.props.server?.id || 0}/database`,
              onlyActivePath: `/servers/${page.props.server?.id || 0}/database`,
              icon: DatabaseIcon,
            },
            {
              title: 'Users',
              href: `/servers/${page.props.server?.id || 0}/database/users`,
              icon: UsersIcon,
            },
            {
              title: 'Backups',
              href: `/servers/${page.props.server?.id || 0}/backups`,
              icon: CloudUploadIcon,
            },
          ],
        },
        {
          title: 'Sites',
          href: `/servers/${page.props.server?.id || 0}/sites`,
          icon: MousePointerClickIcon,
          isDisabled: isServerMenuDisabled,
          children: page.props.site
            ? [
                {
                  title: 'All sites',
                  href: `/servers/${page.props.server?.id || 0}/sites`,
                  onlyActivePath: `/servers/${page.props.server?.id || 0}/sites`,
                  icon: ArrowLeftIcon,
                },
                {
                  title: 'Application',
                  href: `/servers/${page.props.server?.id || 0}/sites/${page.props.site?.id || 0}`,
                  icon: RocketIcon,
                },
              ]
            : [],
        },
        {
          title: 'Firewall',
          href: `/servers/${page.props.server?.id || 0}/firewall`,
          icon: FlameIcon,
          isDisabled: isServerMenuDisabled,
        },
        {
          title: 'CronJobs',
          href: `/servers/${page.props.server?.id || 0}/cronjobs`,
          icon: ClockIcon,
          isDisabled: isServerMenuDisabled,
        },
        // {
        //   title: 'Workers',
        //   href: '#',
        //   icon: ListEndIcon,
        // },
        // {
        //   title: 'SSH Keys',
        //   href: '#',
        //   icon: KeyIcon,
        // },
        // {
        //   title: 'Services',
        //   href: '#',
        //   icon: CogIcon,
        // },
        // {
        //   title: 'Metrics',
        //   href: '#',
        //   icon: ChartPieIcon,
        // },
        // {
        //   title: 'Console',
        //   href: '#',
        //   icon: TerminalSquareIcon,
        // },
        // {
        //   title: 'Logs',
        //   href: '#',
        //   icon: LogsIcon,
        // },
        // {
        //   title: 'Settings',
        //   href: '#',
        //   icon: Settings2Icon,
        // },
      ],
    },
    {
      title: 'Sites',
      href: '/sites',
      icon: MousePointerClickIcon,
    },
    {
      title: 'Settings',
      href: '/settings',
      icon: CogIcon,
      children: [
        {
          title: 'Profile',
          href: '/settings/profile',
          icon: UserIcon,
        },
        {
          title: 'Users',
          href: '/admin/users',
          icon: UsersIcon,
        },
        {
          title: 'Projects',
          href: '/settings/projects',
          icon: ListIcon,
        },
        {
          title: 'Server Providers',
          href: '/settings/server-providers',
          icon: CloudIcon,
        },
        {
          title: 'Source Controls',
          href: '/settings/source-controls',
          icon: CodeIcon,
        },
        {
          title: 'Storage Providers',
          href: '/settings/storage-providers',
          icon: DatabaseIcon,
        },
        {
          title: 'Notification Channels',
          href: '/settings/notification-channels',
          icon: BellIcon,
        },
        {
          title: 'SSH Keys',
          href: '/settings/ssh-keys',
          icon: KeyIcon,
        },
        {
          title: 'Tags',
          href: '/tags',
          icon: TagIcon,
        },
        {
          title: 'API Keys',
          href: '/settings/api-keys',
          icon: PlugIcon,
        },
      ],
    },
  ];

  const footerNavItems: NavItem[] = [
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

  const getMenuItems = (items: NavItem[]) => {
    return items.map((item) => {
      const isActive = item.onlyActivePath ? currentPath() === item.href : currentPath().startsWith(item.href);

      if (item.children && item.children.length > 0) {
        return (
          <Collapsible key={`${item.title}-${item.href}`} defaultOpen={isActive} className="group/collapsible">
            <SidebarMenuItem>
              <CollapsibleTrigger asChild>
                <SidebarMenuButton disabled={item.isDisabled || false}>
                  {item.icon && <item.icon />}
                  <span>{item.title}</span>
                  <ChevronRightIcon className="ml-auto transition-transform group-data-[state=open]/collapsible:rotate-90" />
                </SidebarMenuButton>
              </CollapsibleTrigger>
              <CollapsibleContent>
                <SidebarMenuSub className="">{getMenuItems(item.children)}</SidebarMenuSub>
              </CollapsibleContent>
            </SidebarMenuItem>
          </Collapsible>
        );
      }

      return (
        <SidebarMenuItem key={`${item.title}-${item.href}`}>
          <SidebarMenuButton onClick={() => router.visit(item.href)} isActive={isActive} disabled={item.isDisabled || false}>
            {item.icon && <item.icon />}
            <span>{item.title}</span>
          </SidebarMenuButton>
        </SidebarMenuItem>
      );
    });
  };

  return (
    <Sidebar collapsible="offcanvas" variant="sidebar">
      <SidebarHeader>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton size="sm" asChild>
              <Link href="/servers" prefetch>
                <AppLogo />
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarHeader>

      <SidebarContent>
        <SidebarGroup>
          <SidebarGroupContent>
            <SidebarMenu>{getMenuItems(mainNavItems)}</SidebarMenu>
          </SidebarGroupContent>
        </SidebarGroup>
      </SidebarContent>

      <SidebarFooter>
        <SidebarMenu>
          {footerNavItems.map((item) => (
            <SidebarMenuItem key={`${item.title}-${item.href}`}>
              <SidebarMenuButton asChild tooltip={{ children: item.title, hidden: false }}>
                <a href={item.href} target="_blank" rel="noopener noreferrer">
                  {item.icon && <Icon iconNode={item.icon} />}
                  <span>{item.title}</span>
                </a>
              </SidebarMenuButton>
            </SidebarMenuItem>
          ))}
        </SidebarMenu>
        <NavUser />
      </SidebarFooter>
    </Sidebar>
  );
}
