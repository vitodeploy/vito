import { type NavItem } from '@/types';
import { GithubIcon, PlugIcon, UsersIcon } from 'lucide-react';
import { ReactNode } from 'react';
import Layout from '@/layouts/app/layout';
import VitoIcon from '@/icons/vito';

const sidebarNavItems: NavItem[] = [
  {
    title: 'Users',
    href: '/admin/users',
    icon: UsersIcon,
  },
  {
    title: 'Plugins',
    href: '/admin/plugins',
    icon: PlugIcon,
  },
  {
    title: 'GitHub App',
    href: '/admin/github-app',
    icon: GithubIcon,
  },
  {
    title: 'Vito Settings',
    href: '/admin/vito',
    icon: VitoIcon,
  },
];

export default function SettingsLayout({ children }: { children: ReactNode }) {
  // When server-side rendering, we only render the layout on the client...
  if (typeof window === 'undefined') {
    return null;
  }

  return (
    <Layout secondNavItems={sidebarNavItems} secondNavTitle="Admin">
      {children}
    </Layout>
  );
}
