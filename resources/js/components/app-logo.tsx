import { usePage } from '@inertiajs/react';
import AppLogoIcon from './app-logo-icon';
import { SharedData } from '@/types';

export default function AppLogo() {
  const { env } = usePage<SharedData>().props;
  const isProduction = env === 'production';

  return (
    <div className="relative flex aspect-square size-8 items-center justify-center rounded-md">
      <AppLogoIcon />
      {!isProduction && <div className="absolute right-0 bottom-0 left-0 bg-yellow-400 px-1 text-[8px] leading-tight font-bold text-black">DEV</div>}
    </div>
  );
}
