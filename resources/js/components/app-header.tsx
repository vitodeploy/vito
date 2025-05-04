import { Breadcrumbs } from '@/components/breadcrumbs';
import { Icon } from '@/components/icon';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { NavigationMenu, NavigationMenuItem, NavigationMenuList, navigationMenuTriggerStyle } from '@/components/ui/navigation-menu';
import { Sheet, SheetContent, SheetHeader, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { UserMenuContent } from '@/components/user-menu-content';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import { type BreadcrumbItem, type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BookOpen, CogIcon, Folder, Menu, Search, ServerIcon } from 'lucide-react';
import AppLogo from './app-logo';
import AppLogoIcon from './app-logo-icon';
import { ProjectSwitch } from '@/components/project-switch';

const mainNavItems: NavItem[] = [
  {
    title: 'Servers',
    href: '/servers',
    activePath: '/servers',
    icon: ServerIcon,
  },
  {
    title: 'Settings',
    href: '/settings/profile',
    activePath: '/settings',
    icon: CogIcon,
  },
];

const rightNavItems: NavItem[] = [
  {
    title: 'Repository',
    href: 'https://github.com/vitodeploy/vito',
    icon: Folder,
  },
  {
    title: 'Documentation',
    href: 'https://vitodeploy.com',
    icon: BookOpen,
  },
];

const activeItemStyles = '';

interface AppHeaderProps {
  breadcrumbs?: BreadcrumbItem[];
}

export function AppHeader({ breadcrumbs = [] }: AppHeaderProps) {
  const page = usePage<SharedData>();
  const { auth } = page.props;
  const getInitials = useInitials();
  return (
    <>
      <div className="border-sidebar-border/80 border-b">
        <div className="mx-auto flex h-16 items-center px-4 md:max-w-7xl">
          {/* Mobile Menu */}
          <div className="lg:hidden">
            <Sheet>
              <SheetTrigger asChild>
                <Button variant="ghost" size="icon" className="mr-2 h-[34px] w-[34px]">
                  <Menu className="h-5 w-5" />
                </Button>
              </SheetTrigger>
              <SheetContent side="left" className="bg-sidebar flex h-full w-64 flex-col items-stretch justify-between">
                <SheetTitle className="sr-only">Navigation Menu</SheetTitle>
                <SheetHeader className="flex justify-start text-left">
                  <AppLogoIcon className="h-6 w-6 fill-current text-black dark:text-white" />
                </SheetHeader>
                <div className="flex h-full flex-1 flex-col space-y-4 p-4">
                  <div className="flex h-full flex-col justify-between text-sm">
                    <div className="flex flex-col space-y-4">
                      {mainNavItems.map((item) => (
                        <Link key={item.title} href={item.href} className="flex items-center space-x-2 font-medium">
                          {item.icon && <Icon iconNode={item.icon} className="h-5 w-5" />}
                          <span>{item.title}</span>
                        </Link>
                      ))}
                    </div>

                    <div className="flex flex-col space-y-4">
                      {rightNavItems.map((item) => (
                        <a
                          key={item.title}
                          href={item.href}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="flex items-center space-x-2 font-medium"
                        >
                          {item.icon && <Icon iconNode={item.icon} className="h-5 w-5" />}
                          <span>{item.title}</span>
                        </a>
                      ))}
                    </div>
                  </div>
                </div>
              </SheetContent>
            </Sheet>
          </div>

          <Link href={route('home')} prefetch className="flex items-center space-x-2">
            <AppLogo />
          </Link>

          {/* Desktop Navigation */}
          <div className="ml-6 flex h-full items-center space-x-6">
            <ProjectSwitch />
          </div>

          <div className="ml-auto flex items-center space-x-2">
            <div className="relative flex items-center space-x-1">
              <Button variant="ghost" size="icon" className="group h-9 w-9 cursor-pointer">
                <Search className="!size-5 opacity-80 group-hover:opacity-100" />
              </Button>
              <div className="hidden lg:flex">
                {rightNavItems.map((item) => (
                  <TooltipProvider key={item.title} delayDuration={0}>
                    <Tooltip>
                      <TooltipTrigger>
                        <a
                          href={item.href}
                          target="_blank"
                          rel="noopener noreferrer"
                          className="group text-accent-foreground ring-offset-background hover:bg-accent hover:text-accent-foreground focus-visible:ring-ring ml-1 inline-flex h-9 w-9 items-center justify-center rounded-md bg-transparent p-0 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
                        >
                          <span className="sr-only">{item.title}</span>
                          {item.icon && <Icon iconNode={item.icon} className="size-5 opacity-80 group-hover:opacity-100" />}
                        </a>
                      </TooltipTrigger>
                      <TooltipContent>
                        <p>{item.title}</p>
                      </TooltipContent>
                    </Tooltip>
                  </TooltipProvider>
                ))}
              </div>
            </div>
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" className="size-10 rounded-full p-1">
                  <Avatar className="size-8 overflow-hidden rounded-full">
                    <AvatarImage src={auth.user.avatar} alt={auth.user.name} />
                    <AvatarFallback className="rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                      {getInitials(auth.user.name)}
                    </AvatarFallback>
                  </Avatar>
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent className="w-56" align="end">
                <UserMenuContent user={auth.user} />
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </div>
        {/* Desktop Navigation */}
        <div className="mx-auto hidden h-12 items-center px-2 md:max-w-7xl lg:flex">
          <NavigationMenu className="flex h-full items-stretch">
            <NavigationMenuList className="flex h-full items-stretch">
              {mainNavItems.map((item, index) => (
                <NavigationMenuItem key={index} className="relative flex h-full items-center">
                  <Link
                    href={item.href}
                    className={cn(
                      navigationMenuTriggerStyle(),
                      item.activePath && page.url.startsWith(item.activePath) && activeItemStyles,
                      'h-9 cursor-pointer px-3',
                    )}
                  >
                    {item.icon && <Icon iconNode={item.icon} className="mr-2 h-4 w-4" />}
                    {item.title}
                  </Link>
                  {item.activePath && page.url.startsWith(item.activePath) && (
                    <div className="absolute right-3 bottom-0 left-3 h-0.5 translate-y-px bg-black dark:bg-white"></div>
                  )}
                </NavigationMenuItem>
              ))}
            </NavigationMenuList>
          </NavigationMenu>
        </div>
      </div>
      {/*{breadcrumbs.length > 1 && (*/}
      {/*    <div className="border-sidebar-border/70 flex w-full border-b">*/}
      {/*        <div*/}
      {/*            className="mx-auto flex h-12 w-full items-center justify-start px-4 text-neutral-500 md:max-w-7xl">*/}
      {/*            <Breadcrumbs breadcrumbs={breadcrumbs} />*/}
      {/*        </div>*/}
      {/*    </div>*/}
      {/*)}*/}
    </>
  );
}
