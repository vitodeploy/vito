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
  GlobeIcon,
  HomeIcon,
  KeyIcon,
  ListEndIcon,
  ListIcon,
  LockIcon,
  LogsIcon,
  MousePointerClickIcon,
  NetworkIcon,
  RocketIcon,
  Settings2Icon,
  ShieldIcon,
  SignpostIcon,
  UsersIcon,
  WrenchIcon,
} from 'lucide-react';
import { ReactNode, useEffect } from 'react';
import { Server } from '@/types/server';
import ServerHeader from '@/pages/servers/components/header';
import Layout from '@/layouts/app/layout';
import { usePage } from '@inertiajs/react';
import { Site } from '@/types/site';
import PHPIcon from '@/icons/php';
import siteHelper from '@/lib/site-helper';
import { useRealtimeRecord } from '@/hooks/use-socket-events';

export default function ServerLayout({ children }: { children: ReactNode }) {
  const page = usePage<{
    server: Server;
    site?: Site;
  }>();

  const server = useRealtimeRecord<Server>(page.props.server, 'server')!;
  const isMenuDisabled = server.status !== 'ready';
  const storedSite = siteHelper.getStoredSite();
  const site = page.props.site || (storedSite?.server_id === page.props.server.id ? storedSite : null) || null;

  useEffect(() => {
    if (storedSite && storedSite.server_id !== page.props.server.id) {
      siteHelper.storeSite(undefined);
    }
  }, [page.props.server.id, storedSite]);

  if (typeof window === 'undefined') {
    return null;
  }

  const sidebarNavItems: NavItem[] = [
    {
      title: 'Overview',
      href: `/servers/${page.props.server.id}`,
      onlyActivePath: `/servers/${page.props.server.id}`,
      icon: HomeIcon,
    },
    {
      title: 'Database',
      href: `/servers/${page.props.server.id}/database`,
      icon: DatabaseIcon,
      isDisabled: isMenuDisabled,
      hidden: !page.props.server.services['database'],
      children: [
        {
          title: 'Databases',
          href: `/servers/${page.props.server.id}/database`,
          onlyActivePath: `/servers/${page.props.server.id}/database`,
          icon: DatabaseIcon,
        },
        {
          title: 'Users',
          href: `/servers/${page.props.server.id}/database/users`,
          icon: UsersIcon,
        },
      ],
    },
    {
      title: 'Backups',
      href: `/servers/${page.props.server.id}/backups`,
      icon: CloudUploadIcon,
      isDisabled: isMenuDisabled,
    },
    {
      title: 'Sites',
      href: `/servers/${page.props.server.id}/sites`,
      icon: MousePointerClickIcon,
      isDisabled: isMenuDisabled,
      hidden: !page.props.server.services['webserver'],
      children:
        site && site.id
          ? [
              {
                title: 'All sites',
                href: `/servers/${page.props.server.id}/sites`,
                onlyActivePath: `/servers/${page.props.server.id}/sites`,
                icon: ListIcon,
              },
              {
                title: 'Application',
                href: `/servers/${page.props.server.id}/sites/${site.id}`,
                onlyActivePath: `/servers/${page.props.server.id}/sites/${site.id}`,
                icon: RocketIcon,
              },
              {
                title: 'Domains',
                href: `/servers/${page.props.server.id}/sites/${site.id}/domains`,
                onlyActivePath: `/servers/${page.props.server.id}/sites/${site.id}/domains`,
                icon: GlobeIcon,
              },
              {
                title: 'Features',
                href: `/servers/${page.props.server.id}/sites/${site.id}/features`,
                icon: BoxIcon,
              },
              {
                title: 'Tooling',
                href: `/servers/${page.props.server.id}/sites/${site.id}/tooling`,
                icon: WrenchIcon,
                hidden: site.user === page.props.server.ssh_user || site.status !== 'ready',
              },
              {
                title: 'Commands',
                href: `/servers/${page.props.server.id}/sites/${site.id}/commands`,
                icon: CommandIcon,
              },
              {
                title: 'Workers',
                href: `/servers/${page.props.server.id}/sites/${site.id}/workers`,
                icon: ListEndIcon,
                isDisabled: isMenuDisabled,
                hidden: !page.props.server.services['process_manager'],
              },
              {
                title: 'CronJobs',
                href: `/servers/${page.props.server.id}/sites/${site.id}/cronjobs`,
                icon: ClockIcon,
                isDisabled: isMenuDisabled,
              },
              {
                title: 'Redirects',
                href: `/servers/${page.props.server.id}/sites/${site.id}/redirects`,
                icon: SignpostIcon,
              },
              {
                title: 'Logs',
                href: `/servers/${page.props.server.id}/sites/${site.id}/logs`,
                icon: LogsIcon,
              },
              {
                title: 'Stats',
                href: `/servers/${page.props.server.id}/sites/${site.id}/stats`,
                icon: ChartLineIcon,
                isDisabled: isMenuDisabled,
                hidden: !page.props.server.services['log_analysis'] || !site.stats_enabled,
              },
              {
                title: 'Settings',
                href: `/servers/${page.props.server.id}/sites/${site.id}/settings`,
                icon: Settings2Icon,
              },
            ]
          : [],
    },
    {
      title: 'PHP',
      href: `/servers/${page.props.server.id}/php`,
      icon: PHPIcon,
      isDisabled: isMenuDisabled,
      hidden: !page.props.server.services['php'],
    },
    {
      title: 'Security',
      href: `/servers/${page.props.server.id}/security`,
      icon: ShieldIcon,
      isDisabled: isMenuDisabled,
      children: [
        {
          title: 'General',
          href: `/servers/${page.props.server.id}/security`,
          onlyActivePath: `/servers/${page.props.server.id}/security`,
          icon: ShieldIcon,
        },
        {
          title: 'Firewall',
          href: `/servers/${page.props.server.id}/firewall`,
          onlyActivePath: `/servers/${page.props.server.id}/firewall`,
          icon: FlameIcon,
          hidden: !page.props.server.services['firewall'],
        },
      ],
    },
    {
      title: 'Network',
      href: `/servers/${page.props.server.id}/network`,
      icon: NetworkIcon,
      isDisabled: isMenuDisabled,
    },
    {
      title: 'CronJobs',
      href: `/servers/${page.props.server.id}/cronjobs`,
      icon: ClockIcon,
      isDisabled: isMenuDisabled,
    },
    {
      title: 'Workers',
      href: `/servers/${page.props.server.id}/workers`,
      icon: ListEndIcon,
      isDisabled: isMenuDisabled,
      hidden: !page.props.server.services['process_manager'],
    },
    {
      title: 'SSH Keys',
      href: `/servers/${page.props.server.id}/ssh-keys`,
      icon: KeyIcon,
      isDisabled: isMenuDisabled,
    },
    {
      title: 'SSL',
      href: `/servers/${page.props.server.id}/ssl`,
      icon: LockIcon,
      isDisabled: isMenuDisabled,
    },
    {
      title: 'Services',
      href: `/servers/${page.props.server.id}/services`,
      icon: CogIcon,
      isDisabled: isMenuDisabled,
    },
    {
      title: 'Monitoring',
      href: `/servers/${page.props.server.id}/monitoring`,
      icon: ChartLineIcon,
      isDisabled: isMenuDisabled,
    },
    {
      title: 'Logs',
      href: `/servers/${page.props.server.id}/logs`,
      icon: LogsIcon,
      children: [
        {
          title: 'Server logs',
          href: `/servers/${page.props.server.id}/logs`,
          onlyActivePath: `/servers/${page.props.server.id}/logs`,
          icon: LogsIcon,
        },
        {
          title: 'Service logs',
          href: `/servers/${page.props.server.id}/logs/services`,
          onlyActivePath: `/servers/${page.props.server.id}/logs/services`,
          icon: CogIcon,
        },
        {
          title: 'Custom logs',
          href: `/servers/${page.props.server.id}/logs/remote`,
          onlyActivePath: `/servers/${page.props.server.id}/logs/remote`,
          icon: CloudIcon,
        },
      ],
    },
    {
      title: 'Features',
      href: `/servers/${page.props.server.id}/features`,
      icon: BoxIcon,
      isDisabled: isMenuDisabled,
    },
    {
      title: 'Settings',
      href: `/servers/${page.props.server.id}/settings`,
      icon: Settings2Icon,
    },
  ];

  return (
    <Layout secondNavItems={sidebarNavItems} secondNavTitle={page.props.server.name}>
      <ServerHeader server={server} site={page.props.site} />

      <div>{children}</div>
    </Layout>
  );
}
