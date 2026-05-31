import { CardRow } from '@/components/ui/card';
import { Plugin } from '@/types/plugin';
import { Separator } from '@/components/ui/separator';
import { Badge } from '@/components/ui/badge';
import { Pip } from '@/components/ui/pip';
import PluginDropdown from '@/pages/plugins/components/plugin-dropdown';
import { TriangleAlert } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useDialog } from '@/hooks/use-dialog';

export default function InstalledPlugins({ plugins }: { plugins: Plugin[] }) {
  const dialog = useDialog();
  const installedPlugins = plugins.filter((plugin) => plugin.is_installed);
  return (
    <div>
      {installedPlugins.length > 0 ? (
        installedPlugins.map((plugin, index) => (
          <div key={`plugin-${index}`}>
            <CardRow>
              <div className="flex flex-row items-center gap-4">
                <Pip variant={plugin.is_enabled ? 'default' : plugin.errors.length > 0 ? 'destructive' : 'gray'} />
                <div className="flex flex-col gap-1">
                  <div className="flex items-center gap-2">
                    {plugin.repo === null ? (
                      <span>{plugin.name}</span>
                    ) : (
                      <a href={plugin.repo} className="hover:text-primary" target="_blank">
                        {plugin.name}
                      </a>
                    )}
                    {plugin.username && <Badge variant="outline">by {plugin.username}</Badge>}
                  </div>
                  <div className="text-muted-foreground flex flex-row gap-3 text-xs">
                    <span>{plugin.repo !== null ? 'GitHub' : 'Local'}</span>
                    <span>{plugin.version}</span>
                    {plugin.updates_available && <span>Update Available</span>}
                  </div>
                </div>
              </div>

              <div className="flex flex-row items-center gap-4">
                {plugin.errors.length > 0 && (
                  <Tooltip>
                    <TooltipTrigger asChild>
                      <button
                        type="button"
                        aria-label={`View error logs for ${plugin.name ?? plugin.folder}`}
                        onClick={() => dialog.pluginLogs.open({ name: plugin.name ?? plugin.folder, errors: plugin.errors })}
                      >
                        <TriangleAlert className="text-destructive cursor-pointer" />
                      </button>
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
          <span className="text-muted-foreground">No plugins installed</span>
        </CardRow>
      )}
    </div>
  );
}
