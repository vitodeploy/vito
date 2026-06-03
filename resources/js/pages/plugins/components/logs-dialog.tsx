import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardRow } from '@/components/ui/card';
import { ChevronDownIcon, ChevronRightIcon, FileCodeIcon, AlertCircleIcon, TimerIcon } from 'lucide-react';
import { useState } from 'react';
import { cn } from '@/lib/utils';
import DateTime from '@/components/date-time';
import { PluginError } from '@/types/plugin';

type PluginLogsDialogProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  name: string;
  errors: PluginError[];
};

export default function PluginLogsDialog({ open, onOpenChange, name, errors }: PluginLogsDialogProps) {
  const [expandedItems, setExpandedItems] = useState<Set<string>>(new Set());

  const toggleExpanded = (key: string) => {
    setExpandedItems((prev) => {
      const newSet = new Set(prev);
      if (newSet.has(key)) {
        newSet.delete(key);
      } else {
        newSet.add(key);
      }
      return newSet;
    });
  };

  const rowKey = (error: PluginError) => `${error.occurred_at}-${error.file}-${error.line}`;

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="flex max-h-[80vh] max-w-2xl! flex-col" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Error Logs - {name}</DialogTitle>
          <DialogDescription>
            {errors.length}
            {errors.length === 10 && '+'} error{errors.length !== 1 ? 's' : ''} found in this plugin.
            {errors.length === 10 && 'The most recent 10 will be shown below.'}
          </DialogDescription>
        </DialogHeader>
        <div className="flex-1 overflow-y-auto p-4">
          {errors.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-8 text-center">
              <AlertCircleIcon className="text-muted-foreground mb-3 h-12 w-12" />
              <p className="text-muted-foreground">No errors found</p>
            </div>
          ) : (
            <div className="space-y-3">
              {errors.map((error) => {
                const k = rowKey(error);
                return (
                  <Card key={k} className="overflow-hidden">
                    <CardRow
                      role="button"
                      tabIndex={0}
                      aria-expanded={expandedItems.has(k)}
                      className={cn(
                        'hover:bg-accent/50 focus-visible:ring-ring cursor-pointer transition-colors focus-visible:ring-2 focus-visible:outline-none',
                        expandedItems.has(k) && 'border-b',
                      )}
                      onClick={() => toggleExpanded(k)}
                      onKeyDown={(e) => {
                        if (e.key === 'Enter' || e.key === ' ') {
                          e.preventDefault();
                          toggleExpanded(k);
                        }
                      }}
                    >
                      <div className="flex flex-1 items-start gap-3">
                        <div className="mt-0.5">
                          {expandedItems.has(k) ? (
                            <ChevronDownIcon className="text-muted-foreground h-4 w-4" />
                          ) : (
                            <ChevronRightIcon className="text-muted-foreground h-4 w-4" />
                          )}
                        </div>
                        <div className="min-w-0 flex-1">
                          <p className="text-destructive overflow-wrap-anywhere text-sm font-medium break-words">{error.error_message}</p>
                          <div className="mt-4 flex flex-wrap items-start gap-4">
                            <div className="text-muted-foreground flex min-w-0 items-start gap-1.5 text-xs">
                              <TimerIcon className="h-3 w-3 flex-shrink-0" />
                              <span className="font-mono break-all">
                                <DateTime date={error.occurred_at} />
                              </span>
                            </div>
                          </div>
                          <div className="mt-2 flex flex-wrap items-start gap-4">
                            <div className="text-muted-foreground flex min-w-0 items-start gap-1.5 text-xs">
                              <FileCodeIcon className="h-3 w-3 flex-shrink-0" />
                              <span className="font-mono break-all">
                                {error.file.substring(error.file.indexOf('/app'))}:{error.line}
                              </span>
                            </div>
                          </div>
                        </div>
                      </div>
                    </CardRow>
                    {expandedItems.has(k) && (
                      <CardContent className="bg-muted/30 p-4">
                        <div className="space-y-2">
                          <p className="text-muted-foreground text-xs font-medium tracking-wider uppercase">Stack Trace</p>
                          <pre className="bg-background overflow-x-auto rounded-md p-3 font-mono text-xs break-all whitespace-pre-wrap">
                            {error.stack_trace}
                          </pre>
                        </div>
                      </CardContent>
                    )}
                  </Card>
                );
              })}
            </div>
          )}
        </div>
        <DialogFooter>
          <Button variant="outline" onClick={() => onOpenChange(false)}>
            Close
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
