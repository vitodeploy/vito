import { CardRow } from '@/components/ui/card';
import { Plugin } from '@/types/plugin';
import { Separator } from '@/components/ui/separator';
import PluginDropdown from '@/pages/plugins/components/plugin-dropdown';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { TriangleAlert } from 'lucide-react';

export default function DiscoveredPlugins({ plugins }: { plugins: Plugin[] }) {
  const installedPlugins = plugins.filter((plugin) => !plugin.is_installed);
  return (
    <div>
      {installedPlugins.length > 0 ? (
        installedPlugins.map((plugin, index) => (
          <div key={`plugin-${index}`}>
            <CardRow>
              <div className="flex flex-col gap-1">
                <div className="flex items-center gap-2">{plugin.folder}</div>
              </div>
              <div className="flex items-center gap-4">
                {plugin.errors.length > 0 && (
                  <Tooltip>
                    <TooltipTrigger>
                      <TriangleAlert className="text-danger mt-1" />
                    </TooltipTrigger>
                    <TooltipContent>This plugin has errors</TooltipContent>
                  </Tooltip>
                )}
                <PluginDropdown plugin={plugin} />
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
