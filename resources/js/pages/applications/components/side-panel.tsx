import { ComponentType, ReactNode, useEffect } from 'react';
import { XIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Sheet, SheetContent, SheetTitle } from '@/components/ui/sheet';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { cn } from '@/lib/utils';

export type PanelTab = { value: string; label: string; content: ReactNode };

const editing = (target: EventTarget | null) => !!(target as HTMLElement | null)?.closest('input, textarea, select, [contenteditable="true"]');

export default function SidePanel({ icon: Icon, title, subtitle, actions, tabs, onClose, sheet }: {
  icon: ComponentType<{ className?: string }>;
  title: string;
  subtitle?: ReactNode;
  actions?: ReactNode;
  tabs: PanelTab[];
  onClose: () => void;
  sheet?: boolean;
}) {
  const Title = sheet ? SheetTitle : 'h2';

  useEffect(() => {
    if (sheet) return;
    const close = (event: KeyboardEvent) => {
      if (event.key !== 'Escape' || event.defaultPrevented || editing(event.target)) return;
      if (document.querySelector('[role="dialog"], [role="menu"], [role="listbox"], .monaco-editor')) return;
      onClose();
    };
    window.addEventListener('keydown', close);
    return () => window.removeEventListener('keydown', close);
  }, [onClose, sheet]);

  const content = (
    <Tabs key={tabs.map((tab) => tab.value).join()} defaultValue={tabs[0]?.value} className="min-h-0 flex-1 gap-0">
      <header className={cn('flex flex-col gap-4 border-b px-5 pt-5', tabs.length < 2 && 'pb-5')}>
        <div className="flex items-start gap-3">
          <span className="bg-muted flex size-10 shrink-0 items-center justify-center rounded-lg"><Icon className="size-5" /></span>
          <div className="flex min-w-0 flex-1 flex-col gap-1">
            <Title className="truncate text-lg leading-tight font-semibold">{title}</Title>
            {subtitle && <div className="text-muted-foreground flex flex-wrap items-center gap-x-3 gap-y-1 text-sm">{subtitle}</div>}
          </div>
          <div className="flex shrink-0 items-center gap-1">
            {actions}
            <Button variant="ghost" size="icon" onClick={onClose} aria-label="Close panel"><XIcon /></Button>
          </div>
        </div>
        {tabs.length > 1 && <TabsList className="h-auto gap-4 bg-transparent p-0">
          {tabs.map((tab) => (
            <TabsTrigger
              key={tab.value}
              value={tab.value}
              className="data-[state=active]:border-b-foreground text-muted-foreground data-[state=active]:text-foreground rounded-none border-0 border-b-2 border-transparent px-0 pb-2.5 data-[state=active]:bg-transparent data-[state=active]:shadow-none dark:data-[state=active]:bg-transparent"
            >
              {tab.label}
            </TabsTrigger>
          ))}
        </TabsList>}
      </header>
      {tabs.map((tab) => (
        <TabsContent key={tab.value} value={tab.value} className="flex min-h-0 flex-col gap-5 overflow-y-auto p-5 *:shrink-0">
          {tab.content}
        </TabsContent>
      ))}
    </Tabs>
  );

  return sheet ? (
    <Sheet open onOpenChange={(open) => !open && onClose()}>
      <SheetContent showClose={false} className="w-full gap-0 sm:max-w-3xl" aria-describedby={undefined}
        onEscapeKeyDown={(event) => editing(event.target) && event.preventDefault()}
        onCloseAutoFocus={(event) => event.preventDefault()}
      >
        {content}
      </SheetContent>
    </Sheet>
  ) : (
    <aside className="bg-background absolute inset-y-0 right-0 z-10 flex w-full max-w-2xl flex-col border-l shadow-2xl" aria-label={title}>
      {content}
    </aside>
  );
}
