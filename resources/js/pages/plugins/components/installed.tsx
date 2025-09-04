import { CardRow } from '@/components/ui/card';
import { Plugin } from '@/types/plugin';
import DisablePlugin from '@/pages/plugins/components/disable';
import Uninstall from '@/pages/plugins/components/uninstall';
import EnablePlugin from '@/pages/plugins/components/enable';
import { Separator } from '@/components/ui/separator';
import UpdatePlugin from '@/pages/plugins/components/update';
import { Button } from '@/components/ui/button';
import { Code, Folder } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';

export default function InstalledPlugins({ plugins }: { plugins: Plugin[] }) {
  const installedPlugins = plugins.filter(plugin => plugin.is_installed);
  return (
    <div>
      {installedPlugins.length > 0 ? (
        installedPlugins.map((plugin, index) => (
          <div key={`plugin-${index}`}>
            <CardRow>
              <div className="flex flex-row gap-4 items-center">
                {plugin.repo !== null ? (
                    <Tooltip>
                      <TooltipTrigger asChild>
                        <Button
                          variant="outline"
                          onClick={() => window.open(plugin.repo, '_blank')}
                        >
                          <Code />
                        </Button>
                      </TooltipTrigger>
                      <TooltipContent side="top">Open In GitHub</TooltipContent>
                    </Tooltip>
                ) : (
                      <Button variant="outline" disabled>
                        <Folder />
                      </Button>
                )}
                <div className="flex flex-col gap-1">
                  <div className="flex items-center">{plugin.name}</div>
                  <span className="text-muted-foreground text-xs">
                  {plugin.version}
                  {plugin.updates_available &&
                    " - Update Available!"
                  }
                </span>
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
