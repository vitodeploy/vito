import { useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { ChevronsUpDownIcon, PlusIcon } from 'lucide-react';
import { useInitials } from '@/hooks/use-initials';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { type Site } from '@/types/site';
import type { SharedData } from '@/types';
import CreateSite from '@/pages/sites/components/create-site';
import siteHelper from '@/lib/site-helper';

export function SiteSwitch() {
  const page = usePage<SharedData>();
  const storedSite = siteHelper.getStoredSite();
  const [selectedSite, setSelectedSite] = useState(page.props.site || storedSite || null);
  const initials = useInitials();
  const form = useForm();

  if (storedSite && page.props.site && storedSite.id !== page.props.site.id) {
    siteHelper.storeSite(page.props.site);
  }

  if (storedSite && page.props.server_sites && !page.props.server_sites.find((site) => site.id === storedSite.id)) {
    siteHelper.storeSite();
    setSelectedSite(null);
  }

  const handleSiteChange = (site: Site) => {
    setSelectedSite(site);
    siteHelper.storeSite(site);
    form.post(route('sites.switch', { server: site.server_id, site: site.id }));
  };

  return (
    page.props.server &&
    page.props.server_sites && (
      <div className="flex items-center">
        <DropdownMenu modal={false}>
          <DropdownMenuTrigger asChild>
            <Button variant="ghost" className="px-1!">
              {selectedSite && (
                <>
                  <Avatar className="size-6 rounded-sm">
                    <AvatarFallback className="rounded-sm">{initials(selectedSite?.domain ?? '')}</AvatarFallback>
                  </Avatar>
                  <span className="hidden lg:flex">{selectedSite?.domain}</span>
                </>
              )}

              {!selectedSite && (
                <>
                  <Avatar className="size-6 rounded-sm">
                    <AvatarFallback className="rounded-sm">S</AvatarFallback>
                  </Avatar>
                  <span className="hidden lg:flex">Select a site</span>
                </>
              )}

              <ChevronsUpDownIcon size={5} />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent className="w-56" align="start">
            {page.props.server_sites.length > 0 ? (
              page.props.server_sites.map((site) => (
                <DropdownMenuCheckboxItem
                  key={`site-${site.id.toString()}`}
                  checked={selectedSite?.id === site.id}
                  onCheckedChange={() => handleSiteChange(site)}
                >
                  {site.domain}
                </DropdownMenuCheckboxItem>
              ))
            ) : (
              <DropdownMenuItem disabled>No sites</DropdownMenuItem>
            )}
            <DropdownMenuSeparator />
            <CreateSite server={page.props.server}>
              <DropdownMenuItem className="gap-0" onSelect={(e) => e.preventDefault()}>
                <div className="flex items-center">
                  <PlusIcon size={5} />
                  <span className="ml-2">Create new site</span>
                </div>
              </DropdownMenuItem>
            </CreateSite>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    )
  );
}
