import Container from '@/components/container';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { type NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { ListIcon, LockIcon, UserIcon } from 'lucide-react';
import { type PropsWithChildren } from 'react';

const sidebarNavItems: NavItem[] = [
  {
    title: 'Profile',
    href: '/settings/profile',
    icon: UserIcon,
  },
  {
    title: 'Projects',
    href: '/',
    icon: ListIcon,
  },
  {
    title: 'Password',
    href: '/settings/password',
    icon: LockIcon,
  },
];

export default function SettingsLayout({ children }: PropsWithChildren) {
  // When server-side rendering, we only render the layout on the client...
  if (typeof window === 'undefined') {
    return null;
  }

  const currentPath = window.location.pathname;

  return (
    <Container>
      <div className="flex flex-col space-y-8 lg:flex-row lg:space-y-0 lg:space-x-12">
        <aside className="w-full max-w-xl lg:w-60">
          <nav className="flex flex-col space-y-1 space-x-0">
            {sidebarNavItems.map((item, index) => (
              <Button
                key={`${item.href}-${index}`}
                size="sm"
                variant="ghost"
                asChild
                className={cn('w-full justify-start', {
                  'bg-muted': currentPath === item.href,
                })}
              >
                <Link href={item.href} prefetch>
                  {item.icon && <item.icon />}
                  {item.title}
                </Link>
              </Button>
            ))}
          </nav>
        </aside>

        <Separator className="my-6 md:hidden" />

        <div className="flex-1">
          <section className="space-y-12">{children}</section>
        </div>
      </div>
    </Container>
  );
}
