import { ReactNode, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { CheckIcon, ChevronsUpDownIcon, LayoutGridIcon, PlusIcon, SlashIcon } from 'lucide-react';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { useDialog } from '@/hooks/use-dialog';
import { useInitials } from '@/hooks/use-initials';
import { cn } from '@/lib/utils';
import Status from '@/pages/applications/components/status';
import { Application, ApplicationPermissions, Environment, EnvironmentSummary } from '@/types/application';

type Item = { id: number; name: string; hint?: string; href: string };

function Switcher({ icon, label, items, current, search, footer }: {
  icon: ReactNode;
  label: string;
  items: Item[];
  current?: number;
  search: string;
  footer: { icon: ReactNode; label: string; onSelect: () => void }[];
}) {
  const [open, setOpen] = useState(false);
  const select = (callback: () => void) => {
    setOpen(false);
    callback();
  };

  return (
    <Popover open={open} onOpenChange={setOpen}>
      <PopoverTrigger asChild>
        <Button variant="ghost" className="px-1!" aria-label={label}>
          {icon}
          <span className="hidden lg:flex">{label}</span>
          <ChevronsUpDownIcon size={5} />
        </Button>
      </PopoverTrigger>
      <PopoverContent className="flex max-h-[400px] w-64 flex-col p-0" align="start">
        <Command className="flex flex-col overflow-hidden">
          <CommandInput placeholder={search} />
          <CommandList className="min-h-0 flex-1 overflow-y-auto">
            <CommandEmpty>No results.</CommandEmpty>
            <CommandGroup>
              {items.map((item) => (
                <CommandItem key={item.id} value={`${item.name} ${item.id}`} onSelect={() => select(() => router.visit(item.href))} className="truncate">
                  <span className="truncate">{item.name}</span>
                  {item.hint && <span className="text-muted-foreground truncate font-mono text-xs">{item.hint}</span>}
                  <CheckIcon className={cn('ml-auto', item.id === current ? 'opacity-100' : 'opacity-0')} />
                </CommandItem>
              ))}
            </CommandGroup>
          </CommandList>
          {footer.length > 0 && (
            <CommandGroup className="shrink-0 border-t">
              {footer.map((action) => (
                <CommandItem key={action.label} value={action.label} onSelect={() => select(action.onSelect)}>
                  {action.icon}
                  {action.label}
                </CommandItem>
              ))}
            </CommandGroup>
          )}
        </Command>
      </PopoverContent>
    </Popover>
  );
}

export function ApplicationSwitch() {
  const { application, environment, environments, projectApplications, applicationCan } = usePage<{
    application?: Application;
    environment?: Environment;
    environments: EnvironmentSummary[];
    projectApplications: { id: number; name: string }[];
    applicationCan: ApplicationPermissions;
  }>().props;
  const dialog = useDialog();
  const initials = useInitials();

  return (
    <>
      <Switcher
        icon={<Avatar className="size-6 rounded-sm"><AvatarFallback className="rounded-sm">{application ? initials(application.name) : 'A'}</AvatarFallback></Avatar>}
        label={application?.name ?? 'Select an application'}
        items={projectApplications.map((item) => ({ ...item, href: `/applications/${item.id}` }))}
        current={application?.id}
        search="Search applications..."
        footer={[{ icon: <LayoutGridIcon />, label: 'All applications', onSelect: () => router.visit('/applications') }]}
      />
      {application && environment && (
        <>
          <SlashIcon className="size-3" />
          <Switcher
            icon={<Status status={environment.status} color={environment.status_color} label="" className="size-6 justify-center" />}
            label={environment.name}
            items={environments.map((item) => ({ id: item.id, name: item.name, hint: item.branch, href: `/applications/${application.id}/environments/${item.id}` }))}
            current={environment.id}
            search="Search environments..."
            footer={applicationCan.createEnvironment && application.status === 'ready'
              ? [{ icon: <PlusIcon />, label: 'New environment', onSelect: () => dialog.createEnvironment.open({ application }) }]
              : []}
          />
        </>
      )}
    </>
  );
}
