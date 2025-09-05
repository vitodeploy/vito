import { Plugin } from '@/types/plugin';
import { ChevronDownIcon, ChevronRightIcon, FileCodeIcon, AlertCircleIcon, TimerIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { Card, CardContent, CardRow } from '@/components/ui/card';
import { ReactNode, useState } from 'react';
import { cn } from '@/lib/utils';
import DateTime from '@/components/date-time';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';

export default function ViewLogs({ plugin, children }: { plugin: Plugin; children?: ReactNode }) {
  const [open, setOpen] = useState(false);
  const [expandedItems, setExpandedItems] = useState<Set<number>>(new Set());

  const toggleExpanded = (index: number) => {
    setExpandedItems((prev) => {
      const newSet = new Set(prev);
      if (newSet.has(index)) {
        newSet.delete(index);
      } else {
        newSet.add(index);
      }
      return newSet;
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        {children ? children : <DropdownMenuItem onSelect={(e) => e.preventDefault()}>View Logs</DropdownMenuItem>}
      </DialogTrigger>
      <DialogContent className="flex max-h-[80vh] max-w-2xl! flex-col">
        <DialogHeader>
          <DialogTitle>Error Logs - {plugin.name ?? plugin.folder}</DialogTitle>
          <DialogDescription>
            {plugin.errors.length}
            {plugin.errors.length === 10 && '+'} error{plugin.errors.length !== 1 ? 's' : ''} found in this plugin.
            {plugin.errors.length === 10 && 'The most recent 10 will be shown below.'}
          </DialogDescription>
        </DialogHeader>
        <div className="flex-1 overflow-y-auto p-4">
          {plugin.errors.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-8 text-center">
              <AlertCircleIcon className="text-muted-foreground mb-3 h-12 w-12" />
              <p className="text-muted-foreground">No errors found</p>
            </div>
          ) : (
            <div className="space-y-3">
              {plugin.errors.map((error, index) => (
                <Card key={index} className="overflow-hidden">
                  <CardRow
                    className={cn('hover:bg-accent/50 cursor-pointer transition-colors', expandedItems.has(index) && 'border-b')}
                    onClick={() => toggleExpanded(index)}
                  >
                    <div className="flex flex-1 items-start gap-3">
                      <div className="mt-0.5">
                        {expandedItems.has(index) ? (
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
                  {expandedItems.has(index) && (
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
              ))}
            </div>
          )}
        </div>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Close</Button>
          </DialogClose>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
