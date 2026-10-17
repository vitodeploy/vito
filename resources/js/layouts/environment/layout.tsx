import { ReactNode } from 'react';
import { usePage } from '@inertiajs/react';
import { ArrowLeftIcon, HomeIcon } from 'lucide-react';
import { NavItem } from '@/types';
import { Application, Environment } from '@/types/application';
import Layout from '@/layouts/app/layout';

export default function EnvironmentLayout({ children }: { children: ReactNode }) {
  const { application, environment } = usePage<{ application: Application; environment: Environment }>().props;
  const baseUrl = `/applications/${application.id}/environments/${environment.id}`;
  const sidebarNavItems: NavItem[] = [
    { title: application.name, href: `/applications/${application.id}`, onlyActivePath: `/applications/${application.id}`, icon: ArrowLeftIcon },
    { title: 'Overview', href: baseUrl, onlyActivePath: baseUrl, icon: HomeIcon },
  ];

  return <Layout secondNavItems={sidebarNavItems} secondNavTitle={environment.name}>{children}</Layout>;
}
