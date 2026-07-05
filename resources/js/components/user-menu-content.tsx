import { DropdownMenuGroup, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator } from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user-info';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { type SharedData } from '@/types';
import { type User } from '@/types/user';
import { Link, router, usePage } from '@inertiajs/react';
import { LockKeyhole, LogOut, Settings } from 'lucide-react';
import AppearanceToggleTab from '@/components/appearance-tabs';
import { useBootstrapStore } from '@/stores/bootstrap-store';

interface UserMenuContentProps {
  user: User;
}

export function UserMenuContent({ user }: UserMenuContentProps) {
  const cleanup = useMobileNavigation();
  const { desktop } = usePage<SharedData>().props;
  const isDesktop = desktop?.enabled === true;

  const handleSessionExit = () => {
    cleanup();
    router.flushAll();
    useBootstrapStore.getState().clear();
  };

  return (
    <>
      <DropdownMenuLabel className="p-0 font-normal">
        <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
          <UserInfo user={user} showEmail={true} />
        </div>
      </DropdownMenuLabel>
      <DropdownMenuSeparator />
      <AppearanceToggleTab />
      <DropdownMenuSeparator />
      <DropdownMenuGroup>
        <DropdownMenuItem asChild>
          <Link className="block w-full" href={route('settings')} as="button" prefetch onClick={cleanup}>
            <Settings className="mr-2" />
            Settings
          </Link>
        </DropdownMenuItem>
      </DropdownMenuGroup>
      <DropdownMenuSeparator />
      <DropdownMenuItem asChild>
        <Link className="block w-full" method="post" href={route(isDesktop ? 'desktop.lock' : 'logout')} as="button" onClick={handleSessionExit}>
          {isDesktop ? <LockKeyhole className="mr-2" /> : <LogOut className="mr-2" />}
          {isDesktop ? 'Lock app' : 'Log out'}
        </Link>
      </DropdownMenuItem>
    </>
  );
}
