import { Button } from '@/components/ui/button';
import { Server, ServerWarning } from '@/types/server';
import { BannerItem, WarningsBlock } from '@/components/banners';
import { useDialog } from '@/hooks/use-dialog';

export default function ServerBanners({ server }: { server: Server }) {
  const dialog = useDialog();
  const warnings: ServerWarning[] = server.warnings ?? [];
  const items: BannerItem[] = [];

  const rebootRequiredWarning = warnings.find((w) => w.key === 'reboot_required');
  const updatesWarning = warnings.find((w) => w.key === 'updates_available');
  const kernelUpdateWarning = warnings.find((w) => w.key === 'kernel_update_available');

  if (rebootRequiredWarning) {
    items.push({
      key: 'reboot-required',
      title: 'Restart required',
      description: 'The kernel or a critical package has been updated. Restart the server to complete the upgrade.',
      action: (
        <Button
          variant="outline"
          size="sm"
          onClick={() =>
            dialog.confirm.open({
              title: `Restart ${server.name}?`,
              description:
                'Are you sure you want to restart this server? Sites and services hosted on this server will be unavailable while it restarts. Connections in flight will be dropped.',
              confirmLabel: 'Restart',
              method: 'post',
              url: route('servers.reboot', server.id),
            })
          }
        >
          Restart
        </Button>
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
        <Button
          variant="outline"
          size="sm"
          onClick={() =>
            dialog.confirm.open({
              title: `Update ${server.name}?`,
              description: `Apply ${updatesCount} pending OS package ${updatesCount === 1 ? 'update' : 'updates'} to this server? The upgrade can take several minutes and may briefly restart affected services. A server restart may be required afterwards.`,
              confirmLabel: 'Update',
              method: 'post',
              url: route('servers.update', server.id),
            })
          }
        >
          Update
        </Button>
      ),
    });
  }

  if (kernelUpdateWarning) {
    const kernelCount = kernelUpdateWarning.count;
    items.push({
      key: 'kernel-update',
      title: `Kernel update available`,
      description: <>Install the pending kernel {kernelCount === 1 ? 'package' : 'packages'} and restart to apply the new kernel.</>,
      action: (
        <Button
          variant="outline"
          size="sm"
          onClick={() =>
            dialog.confirm.open({
              title: `Update kernel on ${server.name}?`,
              description: `This installs the pending kernel ${kernelCount === 1 ? 'package' : 'packages'} (a full upgrade that may install or remove packages), then restarts the server to boot the new kernel. The server will be unavailable for a minute or two and connections in flight will be dropped.`,
              variant: 'destructive',
              confirmLabel: 'Update & restart',
              method: 'post',
              url: route('servers.update-kernel', server.id),
            })
          }
        >
          Update &amp; restart
        </Button>
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
