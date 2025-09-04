import { CardRow } from '@/components/ui/card';
import { Plugin } from '@/types/plugin';
import DisablePlugin from '@/pages/plugins/components/disable';
import Uninstall from '@/pages/plugins/components/uninstall';
import EnablePlugin from '@/pages/plugins/components/enable';
import { Separator } from '@/components/ui/separator';
import UpdatePlugin from '@/pages/plugins/components/update';
import { Badge } from '@/components/ui/badge';

export default function InstalledPlugins({ plugins }: { plugins: Plugin[] }) {
  const installedPlugins = plugins.filter(plugin => plugin.is_installed);
  return (
    <div>
      {installedPlugins.length > 0 ? (
        installedPlugins.map((plugin, index) => (
          <div key={`plugin-${index}`}>
            <CardRow>
              <div className="flex flex-row gap-4 items-center">
                <div className="flex flex-col gap-1">
                  <div className="flex items-center gap-2">
                    {plugin.repo === null ? (
                      <span>{plugin.name}</span>
                    ) : (
                      <a href={plugin.repo} className="hover:text-primary" target="_blank">{plugin.name}</a>
                    )}
                    {plugin.username && (
                      <Badge variant="outline">by {plugin.username}</Badge>
                    )}
                  </div>
                  <div className="text-muted-foreground text-xs flex flex-row gap-3">
                    <span>{plugin.repo !== null ? "GitHub" : "Local"}</span>
                    <span>{plugin.version}</span>
                    {plugin.updates_available && (
                      <span>Update Available</span>
                    )}
                </div>
                </div>
              </div>

              <div className="flex flex-row gap-2">
                {plugin.updates_available &&
                  <UpdatePlugin plugin={plugin} />
                }
                {plugin.is_enabled &&
                  <DisablePlugin plugin={plugin} />
                }
                {!plugin.is_enabled &&
                  <EnablePlugin plugin={plugin} />
                }
                {!plugin.is_enabled &&
                  <Uninstall plugin={plugin} />
                }
              </div>
            </CardRow>
            {installedPlugins.length - 1 !== index && <Separator />}
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
