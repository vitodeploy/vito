import { Button } from '@/components/ui/button';
import { Server, ServerWarning } from '@/types/server';
import { BannerItem, WarningsBlock } from '@/components/banners';
import UpdateServer from '@/pages/servers/components/update-server';
import RebootServer from '@/pages/servers/components/reboot-server';

export default function ServerBanners({ server }: { server: Server }) {
  const warnings: ServerWarning[] = server.warnings ?? [];
  const items: BannerItem[] = [];

  const rebootRequiredWarning = warnings.find((w) => w.key === 'reboot_required');
  const updatesWarning = warnings.find((w) => w.key === 'updates_available');

  if (rebootRequiredWarning) {
    items.push({
      key: 'reboot-required',
      title: 'Restart required',
      description: 'The kernel or a critical package has been updated. Restart the server to complete the upgrade.',
      action: (
        <RebootServer server={server}>
          <Button variant="outline" size="sm">
            Restart
          </Button>
        </RebootServer>
      ),
    });
  }

  if (updatesWarning) {
    const updatesCount = updatesWarning.count;
    items.push({
      key: 'package-updates',
      title: `${updatesCount} package ${updatesCount === 1 ? 'update' : 'updates'} available`,
      description: <>Install pending OS package updates to keep this server patched.</>,
      action: (
        <UpdateServer server={server}>
          <Button variant="outline" size="sm">
            Update
          </Button>
        </UpdateServer>
      ),
    });
  }

  if (items.length === 0) return null;

  return (
    <div className="flex flex-col gap-3">
      <WarningsBlock items={items} summaryLabel={(count) => `${count} server warnings require your attention`} />
    </div>
  );
}
