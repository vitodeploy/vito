import { Link, router } from '@inertiajs/react';
import { OctagonAlertIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { humanizeStep } from '@/lib/utils';
import { Site, SiteWarning } from '@/types/site';
import { useState } from 'react';
import { BannerItem, WarningsBlock } from '@/components/banners';

function InstallationFailedBanner({ site }: { site: Site }) {
  const [open, setOpen] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState<string | null>(null);

  const step = humanizeStep(site.progress_step);

  return (
    <div className="border-destructive/40 bg-destructive/5 flex flex-col gap-4 rounded-lg border p-5">
      <div className="flex items-start gap-3">
        <div className="bg-destructive/10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full">
          <OctagonAlertIcon className="text-destructive h-4 w-4" />
        </div>
        <div className="flex min-w-0 flex-1 flex-col gap-1">
          <p className="text-sm leading-tight font-medium">Site installation failed{step ? ` while ${step.toLowerCase()}` : ''}</p>
          <p className="text-muted-foreground text-sm">You can retry the installation; steps that have already completed will be skipped.</p>
        </div>
      </div>

      {site.last_error && (
        <pre className="text-muted-foreground bg-muted/40 ml-11 max-h-40 overflow-auto rounded-md p-3 font-mono text-xs whitespace-pre-wrap">
          {site.last_error}
        </pre>
      )}

      <div className="ml-11">
        <Dialog
          open={open}
          onOpenChange={(next) => {
            setOpen(next);
            if (!next) setSubmitError(null);
          }}
        >
          <DialogTrigger asChild>
            <Button variant="destructive" size="sm">
              Retry installation
            </Button>
          </DialogTrigger>
          <DialogContent>
            <DialogHeader>
              <DialogTitle>Retry site installation?</DialogTitle>
              <DialogDescription>
                This will re-run the installation for <strong>{site.domain}</strong>. Steps that already completed (isolated user, vhost, cloned
                repository, deployed key) will be detected and skipped.
              </DialogDescription>
            </DialogHeader>
            {submitError && <p className="text-destructive text-sm">{submitError}</p>}
            <DialogFooter>
              <Button variant="outline" onClick={() => setOpen(false)} disabled={submitting}>
                Cancel
              </Button>
              <Button
                variant="destructive"
                disabled={submitting}
                onClick={() => {
                  setSubmitting(true);
                  setSubmitError(null);
                  setOpen(false);
                  router.post(
                    route('sites.retry', { server: site.server_id, site: site.id }),
                    {},
                    {
                      preserveScroll: true,
                      onError: (errors) => {
                        const first = errors && typeof errors === 'object' ? Object.values(errors)[0] : null;
                        const message =
                          typeof first === 'string'
                            ? first
                            : Array.isArray(first) && typeof first[0] === 'string'
                              ? first[0]
                              : 'Could not retry installation. Check the site logs.';
                        setSubmitError(message);
                        setOpen(true);
                      },
                      onFinish: () => {
                        setSubmitting(false);
                      },
                    },
                  );
                }}
              >
                {submitting ? 'Retrying...' : 'Retry installation'}
              </Button>
            </DialogFooter>
          </DialogContent>
        </Dialog>
      </div>
    </div>
  );
}

export default function SiteBanners({ site }: { site: Site }) {
  const warnings: SiteWarning[] = site.warnings ?? [];
  const installationFailed = site.status === 'installation_failed';

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

  if (!installationFailed && items.length === 0) {
    return null;
  }

  return (
    <div className="flex flex-col gap-3">
      {installationFailed && <InstallationFailedBanner site={site} />}
      <WarningsBlock items={items} />
    </div>
  );
}
