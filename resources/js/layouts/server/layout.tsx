import { type NavItem } from '@/types';
import {
  BoxIcon,
  ChartLineIcon,
  ClockIcon,
  CloudIcon,
  CloudUploadIcon,
  CogIcon,
  CommandIcon,
  DatabaseIcon,
  FlameIcon,
  HomeIcon,
  KeyIcon,
  ListEndIcon,
  ListIcon,
  LockIcon,
  LogsIcon,
  MousePointerClickIcon,
  RocketIcon,
  Settings2Icon,
  SignpostIcon,
  TerminalSquareIcon,
  UsersIcon,
} from 'lucide-react';
import { ReactNode } from 'react';
import { Server } from '@/types/server';
import ServerHeader from '@/pages/servers/components/header';
import Layout from '@/layouts/app/layout';
import { usePage } from '@inertiajs/react';
import { Site } from '@/types/site';
import PHPIcon from '@/icons/php';
import NodeIcon from '@/icons/node';
import siteHelper from '@/lib/site-helper';

export default function ServerLayout({ children }: { children: ReactNode }) {
  const page = usePage<{
    server: Server;
    site?: Site;
  }>();

  // When server-side rendering, we only render the layout on the client...
  if (typeof window === 'undefined') {
    return null;
  }

  const isMenuDisabled = page.props.server.status !== 'ready';
  const site = page.props.site || siteHelper.getStoredSite() || null;

  const sidebarNavItems: NavItem[] = [
    {
      title: 'Overview',
      href: route('servers.show', { server: page.props.server.id }),
      onlyActivePath: route('servers.show', { server: page.props.server.id }),
      icon: HomeIcon,
    },
    {
      title: 'Database',
      href: route('databases', { server: page.props.server.id }),
      icon: DatabaseIcon,
      isDisabled: isMenuDisabled,
      hidden: !page.props.server.services['database'],
      children: [
        {
          title: 'Databases',
          href: route('databases', { server: page.props.server.id }),
          onlyActivePath: route('databases', { server: page.props.server.id }),
          icon: DatabaseIcon,
        },
        {
          title: 'Users',
          href: route('database-users', { server: page.props.server.id }),
          icon: UsersIcon,
        },
        {
          title: 'Backups',
          href: route('backups', { server: page.props.server.id }),
          icon: CloudUploadIcon,
        },
      ],
    },
    {
      title: 'Sites',
      href: route('sites', { server: page.props.server.id }),
      icon: MousePointerClickIcon,
      isDisabled: isMenuDisabled,
      hidden: !page.props.server.services['webserver'],
      children: site
        ? [
            {
              title: 'All sites',
              href: route('sites', { server: page.props.server.id }),
              onlyActivePath: route('sites', { server: page.props.server.id }),
              icon: ListIcon,
            },
            {
              title: 'Application',
              href: route('application', { server: page.props.server.id, site: site.id }),
              onlyActivePath: route('application', { server: page.props.server.id, site: site.id }),
              icon: RocketIcon,
            },
            {
              title: 'Features',
              href: route('site-features', { server: page.props.server.id, site: site.id }),
              icon: BoxIcon,
            },
            {
              title: 'Commands',
              href: route('commands', { server: page.props.server.id, site: site.id }),
              icon: CommandIcon,
            },
            {
              title: 'SSL',
              href: route('ssls', { server: page.props.server.id, site: site.id }),
              icon: LockIcon,
            },
            {
              title: 'Workers',
              href: route('workers.site', { server: page.props.server.id, site: site.id }),
              icon: ListEndIcon,
              isDisabled: isMenuDisabled,
              hidden: !page.props.server.services['process_manager'],
            },
            {
              title: 'Redirects',
              href: route('redirects', { server: page.props.server.id, site: site.id }),
              icon: SignpostIcon,
            },
            {
              title: 'Logs',
              href: route('sites.logs', { server: page.props.server.id, site: site.id }),
              icon: LogsIcon,
            },
            {
              title: 'Settings',
              href: route('site-settings', { server: page.props.server.id, site: site.id }),
              icon: Settings2Icon,
            },
          ]
        : [],
    },
    {
      title: 'PHP',
      href: route('php', { server: page.props.server.id }),
      icon: PHPIcon,
      isDisabled: isMenuDisabled,
    },
    {
      title: 'NodeJS',
      href: route('node', { server: page.props.server.id }),
      icon: NodeIcon,
      isDisabled: isMenuDisabled,
    },
    {
      title: 'Firewall',
      href: route('firewall', { server: page.props.server.id }),
      icon: FlameIcon,
      isDisabled: isMenuDisabled,
    },
    {
      title: 'CronJobs',
      href: route('cronjobs', { server: page.props.server.id }),
      icon: ClockIcon,
      isDisabled: isMenuDisabled,
    },
    {
      title: 'Workers',
      href: route('workers', { server: page.props.server.id }),
      icon: ListEndIcon,
      isDisabled: isMenuDisabled,
      hidden: !page.props.server.services['process_manager'],
    },
    {
      title: 'SSH Keys',
      href: route('server-ssh-keys', { server: page.props.server.id }),
      icon: KeyIcon,
      isDisabled: isMenuDisabled,
    },
    {
      title: 'Services',
      href: route('services', { server: page.props.server.id }),
      icon: CogIcon,
      isDisabled: isMenuDisabled,
    },
    {
      title: 'Monitoring',
      href: route('monitoring', { server: page.props.server.id }),
      icon: ChartLineIcon,
      isDisabled: isMenuDisabled,
    },
    {
      title: 'Console',
      href: route('console', { server: page.props.server.id }),
      icon: TerminalSquareIcon,
      isDisabled: isMenuDisabled,
    },
    {
      title: 'Logs',
      href: route('logs', { server: page.props.server.id }),
      icon: LogsIcon,
      children: [
        {
          title: 'Server logs',
          href: route('logs', { server: page.props.server.id }),
          onlyActivePath: route('logs', { server: page.props.server.id }),
          icon: LogsIcon,
        },
        {
          title: 'Remote logs',
          href: route('logs.remote', { server: page.props.server.id }),
          onlyActivePath: route('logs.remote', { server: page.props.server.id }),
          icon: CloudIcon,
        },
      ],
    },
    {
      title: 'Settings',
      href: route('server-settings', { server: page.props.server.id }),
      icon: Settings2Icon,
    },
  ];

  return (
    <Layout secondNavItems={sidebarNavItems} secondNavTitle={page.props.server.name}>
      <ServerHeader server={page.props.server} site={page.props.site} />

      <div>{children}</div>
    </Layout>
  );
}
