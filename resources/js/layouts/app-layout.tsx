import AppLayout from '@/layouts/app/layout';
import { type BreadcrumbItem } from '@/types';
import { type ReactNode } from 'react';

interface AppLayoutProps {
  children: ReactNode;
  breadcrumbs?: BreadcrumbItem[];
}

export default ({ children, ...props }: AppLayoutProps) => <AppLayout {...props}>{children}</AppLayout>;
