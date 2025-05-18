import { type BreadcrumbItem, type NavItem } from '@/types';
import { CloudIcon, CodeIcon, DatabaseIcon, ListIcon, UserIcon, UsersIcon } from 'lucide-react';
import { ReactNode } from 'react';
import Layout from '@/layouts/app/layout';

const sidebarNavItems: NavItem[] = [
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
];

export default function SettingsLayout({ children, breadcrumbs }: { children: ReactNode; breadcrumbs?: BreadcrumbItem[] }) {
  // When server-side rendering, we only render the layout on the client...
  if (typeof window === 'undefined') {
    return null;
  }

  return (
    <Layout breadcrumbs={breadcrumbs} secondNavItems={sidebarNavItems} secondNavTitle="Settings">
      {children}
    </Layout>
  );
}
