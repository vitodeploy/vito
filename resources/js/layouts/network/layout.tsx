import { type NavItem } from '@/types';
import { FlameIcon, HomeIcon, LaptopIcon, LogsIcon, ServerIcon, Settings2Icon } from 'lucide-react';
import { ReactNode } from 'react';
import { Network } from '@/types/network';
import Layout from '@/layouts/app/layout';
import { usePage } from '@inertiajs/react';
import { useRealtimeRecord } from '@/hooks/use-socket-events';

export default function NetworkLayout({ children }: { children: ReactNode }) {
  const page = usePage<{ network: Network }>();
  const network = useRealtimeRecord<Network>(page.props.network, 'network')!;

  if (typeof window === 'undefined') {
    return null;
  }

  const sidebarNavItems: NavItem[] = [
    {
      title: 'Overview',
      href: route('networks.show', { network: network.id }),
      onlyActivePath: route('networks.show', { network: network.id }),
      icon: HomeIcon,
    },
    {
      title: 'Servers',
      href: route('networks.servers', { network: network.id }),
      icon: ServerIcon,
    },
    {
      title: 'Peers',
      href: route('networks.peers', { network: network.id }),
      icon: LaptopIcon,
      isDisabled: network.type_value !== 'wireguard',
    },
    {
      title: 'Firewall',
      href: route('networks.firewall', { network: network.id }),
      icon: FlameIcon,
    },
    {
      title: 'Logs',
      href: route('networks.logs', { network: network.id }),
      icon: LogsIcon,
    },
    {
      title: 'Settings',
      href: route('networks.settings', { network: network.id }),
      icon: Settings2Icon,
    },
  ];

  return (
    <Layout secondNavItems={sidebarNavItems} secondNavTitle={network.name}>
      <div>{children}</div>
    </Layout>
  );
}
