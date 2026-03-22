import { Link, router } from '@inertiajs/react';
import { ChevronDownIcon, TriangleAlertIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { Site, SiteWarning } from '@/types/site';
import { ReactNode, useState } from 'react';

interface BannerItem {
  key: string;
  title: string;
  description: ReactNode;
  action?: ReactNode;
}

function BannerRow({ item }: { item: BannerItem }) {
  return (
    <div className="flex items-center gap-4 px-4 py-3">
      <TriangleAlertIcon className="text-warning h-4 w-4 shrink-0" />
      <div className="min-w-0 flex-1 text-sm">
        <p className="font-medium">{item.title}</p>
        <p className="text-muted-foreground mt-0.5">{item.description}</p>
      </div>
      {item.action && <div className="shrink-0">{item.action}</div>}
    </div>
  );
}

export default function SiteBanners({ site }: { site: Site }) {
  const warnings: SiteWarning[] = site.warnings ?? [];
  const [open, setOpen] = useState(false);

  const pendingDomainsWarning = warnings.find((w) => w.key === 'pending_domains');
  const sslDisabledWarning = warnings.find((w) => w.key === 'ssl_disabled');
  const vhostWarning = warnings.find((w) => w.key === 'vhost_generation_disabled');
  const sslExpiringWarning = warnings.find((w) => w.key === 'ssl_expiring');

  const items: BannerItem[] = [];

  if (sslDisabledWarning) {
    items.push({
      key: 'ssl-disabled',
      title: 'SSL is disabled',
      description: 'This site will not be served over HTTPS even if valid certificates exist. Enable SSL to serve the site securely.',
      action: (
        <Button
          variant="outline"
          size="sm"
          onClick={() => {
            router.post(route('sites.enable-ssl', { server: site.server_id, site: site.id }), {}, { preserveScroll: true });
          }}
        >
          Enable SSL
        </Button>
      ),
    });
  }

  if (pendingDomainsWarning && pendingDomainsWarning.key === 'pending_domains') {
    items.push({
      key: 'pending-domains',
      title: `${pendingDomainsWarning.count} pending ${pendingDomainsWarning.count === 1 ? 'domain' : 'domains'}`,
      description: (
        <>
          We could not confirm that <strong>{pendingDomainsWarning.domains.join(', ')}</strong> {pendingDomainsWarning.count === 1 ? 'is' : 'are'}{' '}
          pointing to this server. Update your DNS records or activate by force via the Manage Domains page.
        </>
      ),
      action: (
        <Link href={route('hosted-domains', { server: site.server_id, site: site.id })}>
          <Button variant="outline" size="sm">
            Manage Domains
          </Button>
        </Link>
      ),
    });
  }

  if (sslExpiringWarning && sslExpiringWarning.key === 'ssl_expiring') {
    const daysLeft = Math.max(0, Math.ceil((new Date(sslExpiringWarning.earliest_expiry).getTime() - Date.now()) / 86400000));
    items.push({
      key: 'ssl-expiring',
      title: `${sslExpiringWarning.count} SSL ${sslExpiringWarning.count === 1 ? 'certificate' : 'certificates'} expiring in ${daysLeft} ${daysLeft === 1 ? 'day' : 'days'}`,
      description: (
        <>
          SSL certificates for <strong>{sslExpiringWarning.domains.join(', ')}</strong>{' '}
          {daysLeft === 0 ? 'expire today.' : `will expire in ${daysLeft} ${daysLeft === 1 ? 'day' : 'days'}.`}
        </>
      ),
      action: (
        <Link href={route('hosted-domains', { server: site.server_id, site: site.id })}>
          <Button variant="outline" size="sm">
            Manage Domains
          </Button>
        </Link>
      ),
    });
  }

  if (vhostWarning) {
    items.push({
      key: 'vhost-disabled',
      title: 'VHost generation is disabled',
      description: (
        <>
          Automatic VHost generation has been disabled. Changes to SSL, domains, or redirects will not update the VHost config. Review your template
          on the{' '}
          <Link href={route('site-settings', { server: site.server_id, site: site.id })} className="underline">
            Settings page
          </Link>{' '}
          before re-enabling.
        </>
      ),
      action: (
        <Button
          variant="outline"
          size="sm"
          onClick={() => {
            router.patch(
              route('site-settings.update-vhost-generation', { server: site.server_id, site: site.id }),
              { vhost_generation_enabled: true },
              { preserveScroll: true },
            );
          }}
        >
          Re-enable
        </Button>
      ),
    });
  }

  if (items.length === 0) {
    return null;
  }

  if (items.length === 1) {
    return (
      <div className="border-warning/40 bg-warning/5 rounded-lg border">
        <BannerRow item={items[0]} />
      </div>
    );
  }

  return (
    <Collapsible open={open} onOpenChange={setOpen}>
      <div className="border-warning/40 bg-warning/5 rounded-lg border">
        <CollapsibleTrigger className="flex w-full cursor-pointer items-center gap-3 px-4 py-2.5 text-left">
          <TriangleAlertIcon className="text-warning h-4 w-4 shrink-0" />
          <span className="flex-1 text-sm font-medium">{items.length} warnings require your attention</span>
          <ChevronDownIcon className={`text-muted-foreground h-4 w-4 shrink-0 transition-transform duration-200 ${open ? 'rotate-180' : ''}`} />
        </CollapsibleTrigger>

        <CollapsibleContent>
          <div className="border-warning/25 space-y-0 border-t">
            {items.map((item, i) => (
              <div key={item.key} className={i > 0 ? 'border-warning/25 border-t' : ''}>
                <BannerRow item={item} />
              </div>
            ))}
          </div>
        </CollapsibleContent>
      </div>
    </Collapsible>
  );
}
