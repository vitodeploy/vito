import { ReactNode, useState } from 'react';
import { ChevronDownIcon, TriangleAlertIcon } from 'lucide-react';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';

export interface BannerItem {
  key: string;
  title: string;
  description: ReactNode;
  action?: ReactNode;
}

export function BannerRow({ item }: { item: BannerItem }) {
  return (
    <div className="flex items-center gap-4 px-4 py-3">
      <TriangleAlertIcon className="text-warning h-4 w-4 shrink-0" />
      <div className="min-w-0 flex-1 text-sm">
        <p className="font-medium">{item.title}</p>
        <p className="text-muted-foreground mt-0.5">{item.description}</p>
      </div>
      {item.action && <div className="shrink-0">{item.action}</div>}
    </div>
  );
}

export function WarningsBlock({ items, summaryLabel }: { items: BannerItem[]; summaryLabel?: (count: number) => string }) {
  const [open, setOpen] = useState(false);

  if (items.length === 0) return null;

  if (items.length === 1) {
    return (
      <div className="border-warning/40 bg-warning/5 rounded-lg border">
        <BannerRow item={items[0]} />
      </div>
    );
  }

  const label = summaryLabel ? summaryLabel(items.length) : `${items.length} warnings require your attention`;

  return (
    <Collapsible open={open} onOpenChange={setOpen}>
      <div className="border-warning/40 bg-warning/5 rounded-lg border">
        <CollapsibleTrigger className="flex w-full cursor-pointer items-center gap-3 px-4 py-2.5 text-left">
          <TriangleAlertIcon className="text-warning h-4 w-4 shrink-0" />
          <span className="flex-1 text-sm font-medium">{label}</span>
          <ChevronDownIcon className={`text-muted-foreground h-4 w-4 shrink-0 transition-transform duration-200 ${open ? 'rotate-180' : ''}`} />
        </CollapsibleTrigger>

        <CollapsibleContent>
          <div className="border-warning/25 space-y-0 border-t">
            {items.map((item, i) => (
              <div key={item.key} className={i > 0 ? 'border-warning/25 border-t' : ''}>
                <BannerRow item={item} />
              </div>
            ))}
          </div>
        </CollapsibleContent>
      </div>
    </Collapsible>
  );
}
