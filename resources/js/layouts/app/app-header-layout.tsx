import { AppContent } from '@/components/app-content';
import { AppHeader } from '@/components/app-header';
import { AppShell } from '@/components/app-shell';
import { type BreadcrumbItem } from '@/types';
import type { PropsWithChildren } from 'react';
import { usePoll } from '@inertiajs/react';

export default function AppHeaderLayout({ children }: PropsWithChildren<{ breadcrumbs?: BreadcrumbItem[] }>) {
  usePoll(10000);

  return (
    <AppShell>
      <AppHeader />
      <AppContent>{children}</AppContent>
    </AppShell>
  );
}
