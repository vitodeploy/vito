import { type NavItem } from '@/types';
import {
  ChartPieIcon,
  ClockIcon,
  CogIcon,
  DatabaseIcon,
  FlameIcon,
  FolderOpenIcon,
  HomeIcon,
  KeyIcon,
  ListEndIcon,
  LogsIcon,
  MousePointerClickIcon,
  Settings2Icon,
  TerminalSquareIcon,
} from 'lucide-react';
import { ReactNode } from 'react';
import { Server } from '@/types/server';
import ServerHeader from '@/pages/servers/partials/header';
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
      icon: HomeIcon,
    },
    {
      title: 'Databases',
      href: '#',
      icon: DatabaseIcon,
    },
    {
      title: 'Sites',
      href: '#',
      icon: MousePointerClickIcon,
    },
    {
      title: 'File Manager',
      href: '#',
      icon: FolderOpenIcon,
    },
    {
      title: 'Firewall',
      href: '#',
      icon: FlameIcon,
    },
    {
      title: 'CronJobs',
      href: '#',
      icon: ClockIcon,
    },
    {
      title: 'Workers',
      href: '#',
      icon: ListEndIcon,
    },
    {
      title: 'SSH Keys',
      href: '#',
      icon: KeyIcon,
    },
    {
      title: 'Services',
      href: '#',
      icon: CogIcon,
    },
    {
      title: 'Metrics',
      href: '#',
      icon: ChartPieIcon,
    },
    {
      title: 'Console',
      href: '#',
      icon: TerminalSquareIcon,
    },
    {
      title: 'Logs',
      href: '#',
      icon: LogsIcon,
    },
    {
      title: 'Settings',
      href: '#',
      icon: Settings2Icon,
    },
  ];

  return (
    <Layout secondNavItems={sidebarNavItems} secondNavTitle={server.name}>
      <ServerHeader server={server} />

      <div className="p-4">{children}</div>
    </Layout>
  );
}
