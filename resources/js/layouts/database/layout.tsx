import { Server } from '@/types/server';
import { ReactNode } from 'react';
import ServerLayout from '@/layouts/server/layout';
import Container from '@/components/container';
import { NavigationMenu, NavigationMenuLink, NavigationMenuList } from '@/components/ui/navigation-menu';
import type { NavItem } from '@/types';
import { CloudUploadIcon, DatabaseIcon, UsersIcon } from 'lucide-react';
import { Link } from '@inertiajs/react';

export default function DatabaseLayout({ server, children }: { server: Server; children: ReactNode }) {
  const navItems: NavItem[] = [
    {
      title: 'Databases',
      href: route('databases', { server: server.id }),
      icon: DatabaseIcon,
    },
    {
      title: 'Users',
      href: '/database-users',
      icon: UsersIcon,
    },
    {
      title: 'Backups',
      href: '/backups',
      icon: CloudUploadIcon,
    },
  ];

  return (
    <ServerLayout server={server}>
      <Container className="max-w-5xl">
        <div className="bg-muted/10 inline-flex rounded-md border">
          <NavigationMenu className="flex">
            <NavigationMenuList>
              {navItems.map((item: NavItem) => (
                <NavigationMenuLink
                  key={item.title}
                  asChild
                  className={
                    (item.onlyActivePath ? window.location.href === item.href : window.location.href.startsWith(item.href)) ? 'bg-muted' : ''
                  }
                >
                  <Link href={item.href}>
                    <div className="flex items-center gap-2">
                      {item.icon && <item.icon />}
                      {item.title}
                    </div>
                  </Link>
                </NavigationMenuLink>
              ))}
            </NavigationMenuList>
          </NavigationMenu>
        </div>
      </Container>

      {children}
    </ServerLayout>
  );
}
