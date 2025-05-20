import { type NavItem } from '@/types';
import { CloudUploadIcon, DatabaseIcon, HomeIcon, UsersIcon } from 'lucide-react';
import { ReactNode } from 'react';
import { Server } from '@/types/server';
import ServerHeader from '@/pages/servers/components/header';
import Layout from '@/layouts/app/layout';

export default function ServerLayout({ server, children }: { server: Server; children: ReactNode }) {
  // When server-side rendering, we only render the layout on the client...
  if (typeof window === 'undefined') {
    return null;
  }
  const sidebarNavItems: NavItem[] = [
    {
      title: 'Overview',
      href: route('servers.show', { server: server.id }),
      onlyActivePath: route('servers.show', { server: server.id }),
      icon: HomeIcon,
    },
    {
      title: 'Database',
      href: route('databases', { server: server.id }),
      icon: DatabaseIcon,
      children: [
        {
          title: 'Databases',
          href: route('databases', { server: server.id }),
          icon: DatabaseIcon,
        },
        {
          title: 'Users',
          href: '/users',
          icon: UsersIcon,
        },
        {
          title: 'Backups',
          href: '/backups',
          icon: CloudUploadIcon,
        },
      ],
    },
    // {
    //   title: 'Sites',
    //   href: '#',
    //   icon: MousePointerClickIcon,
    // },
    // {
    //   title: 'Firewall',
    //   href: '#',
    //   icon: FlameIcon,
    // },
    // {
    //   title: 'CronJobs',
    //   href: '#',
    //   icon: ClockIcon,
    // },
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
  ];

  return (
    <Layout secondNavItems={sidebarNavItems} secondNavTitle={server.name}>
      <ServerHeader server={server} />

      <div>{children}</div>
    </Layout>
  );
}
