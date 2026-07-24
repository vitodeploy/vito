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
      href: route('servers'),
      icon: ServerIcon,
      children: [
        {
          title: 'Overview',
          href: route('servers.show', { server: page.props.server?.id || 0 }),
          onlyActivePath: route('servers.show', { server: page.props.server?.id || 0 }),
          icon: HomeIcon,
          isDisabled: isServerMenuDisabled,
        },
        {
          title: 'Database',
          href: route('databases', { server: page.props.server?.id || 0 }),
          icon: DatabaseIcon,
          isDisabled: isServerMenuDisabled,
          children: [
            {
              title: 'Databases',
              href: route('databases', { server: page.props.server?.id || 0 }),
              onlyActivePath: route('databases', { server: page.props.server?.id || 0 }),
              icon: DatabaseIcon,
            },
            {
              title: 'Users',
              href: route('database-users', { server: page.props.server?.id || 0 }),
              icon: UsersIcon,
            },
            {
              title: 'Backups',
              href: route('backups', { server: page.props.server?.id || 0 }),
              icon: CloudUploadIcon,
            },
          ],
        },
        {
          title: 'Sites',
          href: route('sites', { server: page.props.server?.id || 0 }),
          icon: MousePointerClickIcon,
          isDisabled: isServerMenuDisabled,
          children: page.props.site
            ? [
                {
                  title: 'All sites',
                  href: route('sites', { server: page.props.server?.id || 0 }),
                  onlyActivePath: route('sites', { server: page.props.server?.id || 0 }),
                  icon: ArrowLeftIcon,
                },
                {
                  title: 'Application',
                  href: route('application', { server: page.props.server?.id || 0, site: page.props.site?.id || 0 }),
                  icon: RocketIcon,
                },
              ]
            : [],
        },
        {
          title: 'Firewall',
          href: route('firewall', { server: page.props.server?.id || 0 }),
          icon: FlameIcon,
          isDisabled: isServerMenuDisabled,
        },
        {
          title: 'CronJobs',
          href: route('cronjobs', { server: page.props.server?.id || 0 }),
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
      href: route('sites.all'),
      icon: MousePointerClickIcon,
    },
    {
      title: 'Settings',
      href: route('settings'),
      icon: CogIcon,
      children: [
        {
          title: 'Profile',
          href: route('profile'),
          icon: UserIcon,
        },
        {
          title: 'Users',
          href: route('users'),
          icon: UsersIcon,
        },
        {
          title: 'Projects',
          href: route('projects'),
          icon: ListIcon,
        },
        {
          title: 'Server Providers',
          href: route('server-providers'),
          icon: CloudIcon,
        },
        {
          title: 'Source Controls',
          href: route('source-controls'),
          icon: CodeIcon,
        },
        {
          title: 'Storage Providers',
          href: route('storage-providers'),
          icon: DatabaseIcon,
        },
        {
          title: 'Notification Channels',
          href: route('notification-channels'),
          icon: BellIcon,
        },
        {
          title: 'SSH Keys',
          href: route('ssh-keys'),
          icon: KeyIcon,
        },
        {
          title: 'Tags',
          href: route('tags'),
          icon: TagIcon,
        },
        {
          title: 'API Keys',
          href: route('api-keys'),
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
      const isActive = item.onlyActivePath ? currentPath() === item.href : window.location.href.startsWith(item.href);

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
              <Link href={route('servers')} prefetch>
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
