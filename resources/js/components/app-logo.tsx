import AppLogoIcon from './app-logo-icon';

export default function AppLogo() {
  return (
    <>
      <div className="flex aspect-square size-8 items-center justify-center rounded-md">
        <AppLogoIcon className="size-7 rounded-sm fill-current text-white dark:text-black" />
      </div>
    </>
  );
}
