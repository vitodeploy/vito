import { CardRow } from '@/components/ui/card';
import React from 'react';
import { Plugin } from '@/types/plugin';
import Uninstall from '@/pages/plugins/components/uninstall';

export default function InstalledPlugins({ plugins }: { plugins: Plugin[] }) {
  return (
    <div>
      {plugins.length > 0 ? (
        plugins.map((plugin, index) => (
          <CardRow key={`plugin-${index}`}>
            <div className="flex flex-col gap-1">
              <div className="flex items-center gap-2">{plugin.name}</div>
              <span className="text-muted-foreground text-xs">{plugin.version}</span>
            </div>
            <div className="flex items-center gap-2">
              <Uninstall plugin={plugin} />
            </div>
          </CardRow>
        ))
      ) : (
        <CardRow className="items-center justify-center">
          <span className="text-muted-foreground">No plugins installed</span>
        </CardRow>
      )}
    </div>
  );
}
