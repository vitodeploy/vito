import { Button } from '@/components/ui/button';
import { Server } from '@/types/server';
import { BannerItem, WarningsBlock } from '@/components/banners';
import UpdateServer from '@/pages/servers/components/update-server';
import RebootServer from '@/pages/servers/components/reboot-server';

export default function ServerBanners({ server, rebootRequired }: { server: Server; rebootRequired?: boolean }) {
  const items: BannerItem[] = [];

  if (rebootRequired) {
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

  const updatesCount = server.updates ?? 0;
  if (updatesCount > 0) {
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
