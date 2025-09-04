import { CardRow } from '@/components/ui/card';
import { Plugin } from '@/types/plugin';
import { Separator } from '@/components/ui/separator';
import InstallPlugin from '@/pages/plugins/components/install';

export default function DiscoveredPlugins({ plugins }: { plugins: Plugin[] }) {
  const installedPlugins = plugins.filter(plugin => !plugin.is_installed);
  return (
    <div>
      {installedPlugins.length > 0 ? (
        installedPlugins.map((plugin, index) => (
          <div key={`plugin-${index}`}>
            <CardRow>
              <div className="flex flex-col gap-1">
                <div className="flex items-center gap-2">{plugin.folder}</div>
              </div>
              <div className="flex items-center gap-2">
                <InstallPlugin plugin={plugin} />
              </div>
            </CardRow>
            {installedPlugins.length - 1 !== index && <Separator />}
          </div>
        ))
      ) : (
        <CardRow className="items-center justify-center">
          <span className="text-muted-foreground">No uninstalled plugins discovered</span>
        </CardRow>
      )}
    </div>
  );
}
