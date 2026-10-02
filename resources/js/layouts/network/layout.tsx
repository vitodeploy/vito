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
      href: `/networks/${network.id}`,
      onlyActivePath: `/networks/${network.id}`,
      icon: HomeIcon,
    },
    {
      title: 'Servers',
      href: `/networks/${network.id}/servers`,
      icon: ServerIcon,
    },
    {
      title: 'Peers',
      href: `/networks/${network.id}/peers`,
      icon: LaptopIcon,
      isDisabled: network.type_value !== 'wireguard',
    },
    {
      title: 'Firewall',
      href: `/networks/${network.id}/firewall`,
      icon: FlameIcon,
    },
    {
      title: 'Logs',
      href: `/networks/${network.id}/logs`,
      icon: LogsIcon,
    },
    {
      title: 'Settings',
      href: `/networks/${network.id}/settings`,
      icon: Settings2Icon,
    },
  ];

  return (
    <Layout secondNavItems={sidebarNavItems} secondNavTitle={network.name}>
      <div>{children}</div>
    </Layout>
  );
}
