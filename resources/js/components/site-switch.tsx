import { type SharedData } from '@/types';
import { type Site } from '@/types/site';
import { useForm, usePage } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { ChevronsUpDownIcon, PlusIcon } from 'lucide-react';
import { useInitials } from '@/hooks/use-initials';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import CreateSite from '@/pages/sites/components/create-site';
import SiteSelect from '@/pages/sites/components/site-select';
import { CommandGroup, CommandItem } from '@/components/ui/command';
import siteHelper from '@/lib/site-helper';

export function SiteSwitch() {
  const page = usePage<SharedData>();
  const [open, setOpen] = useState(false);
  const [siteFormOpen, setSiteFormOpen] = useState(false);
  const storedSite = siteHelper.getStoredSite();
  const currentSite = page.props.site || null;
  const [selected, setSelected] = useState<string>(currentSite?.id?.toString() ?? '');
  const initials = useInitials();
  const form = useForm();

  useEffect(() => {
    const site = page.props.site || null;
    setSelected(site?.id?.toString() ?? '');
  }, [page.props.site?.id, page.props.server?.id]);

  useEffect(() => {
    const currentStoredSite = siteHelper.getStoredSite();
    if (currentStoredSite && page.props.site && currentStoredSite.id !== page.props.site.id) {
      siteHelper.storeSite(page.props.site);
    }
  }, [page.props.site]);

  useEffect(() => {
    if (storedSite && page.props.server && storedSite.server_id !== page.props.server.id) {
      siteHelper.storeSite(undefined);
      setSelected('');
    }
  }, [page.props.server?.id, storedSite]);

  const handleSiteChange = (value: string, site: Site) => {
    if (!site || !site.id || !site.server_id) {
      setSelected(value);
      setOpen(false);
      return;
    }
    setSelected(value);
    setOpen(false);
    siteHelper.storeSite(site);
    form.post(route('sites.switch', { server: site.server_id, site: site.id }));
  };

  const footer = (
    <CommandGroup>
      <CreateSite defaultOpen={siteFormOpen} onOpenChange={setSiteFormOpen} server={page.props.server}>
        <CommandItem
          value="create-site"
          onSelect={() => {
            setSiteFormOpen(true);
          }}
          className="gap-0"
        >
          <div className="flex items-center">
            <PlusIcon size={5} />
            <span className="ml-2">Create new site</span>
          </div>
        </CommandItem>
      </CreateSite>
    </CommandGroup>
  );

  const trigger = (
    <Button variant="ghost" className="px-1!">
      {currentSite ? (
        <>
          <Avatar className="size-6 rounded-sm">
            <AvatarFallback className="rounded-sm">{initials(currentSite?.domain ?? '')}</AvatarFallback>
          </Avatar>
          <span className="hidden lg:flex">{currentSite?.domain}</span>
        </>
      ) : (
        <>
          <Avatar className="size-6 rounded-sm">
            <AvatarFallback className="rounded-sm">S</AvatarFallback>
          </Avatar>
          <span className="hidden lg:flex">Select a site</span>
        </>
      )}
      <ChevronsUpDownIcon size={5} />
    </Button>
  );

  return (
    page.props.server && (
      <div className="flex items-center">
        <SiteSelect
          serverId={page.props.server.id}
          value={selected}
          onValueChangeAdvanced={handleSiteChange}
          trigger={trigger}
          open={open}
          onOpenChange={setOpen}
          footer={footer}
        />
      </div>
    )
  );
}
