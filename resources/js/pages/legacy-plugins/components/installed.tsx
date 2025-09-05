import { CardRow } from '@/components/ui/card';
import { LegacyPlugin } from '@/types/plugin';
import Uninstall from '@/pages/legacy-plugins/components/uninstall';
import { Separator } from '@/components/ui/separator';

export default function InstalledPlugins({ plugins }: { plugins: LegacyPlugin[] }) {
  return (
    <div>
      {plugins.length > 0 ? (
        plugins.map((plugin, index) => (
          <div key={`plugin-${index}`}>
            <CardRow>
              <div className="flex flex-col gap-1">
                <div className="flex items-center gap-2">{plugin.name}</div>
                <span className="text-muted-foreground text-xs">{plugin.version}</span>
              </div>
              <div className="flex items-center gap-2">
                <Uninstall plugin={plugin} />
              </div>
            </CardRow>
            {plugins.length - 1 !== index && <Separator />}
          </div>
        ))
      ) : (
        <CardRow className="items-center justify-center">
          <span className="text-muted-foreground">No plugins installed</span>
        </CardRow>
      )}
    </div>
  );
}
