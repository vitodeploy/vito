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
import { useState } from 'react';
import { cn } from '@/lib/utils';
import DateTime from '@/components/date-time';
import { DropdownMenuItem } from '@/components/ui/dropdown-menu';

export default function ViewLogs({ plugin }: { plugin: Plugin }) {
  const [open, setOpen] = useState(false);
  const [expandedItems, setExpandedItems] = useState<Set<number>>(new Set());

  const toggleExpanded = (index: number) => {
    setExpandedItems(prev => {
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
        <DropdownMenuItem onSelect={(e) => e.preventDefault()}>View Logs</DropdownMenuItem>
      </DialogTrigger>
      <DialogContent className="max-w-2xl! max-h-[80vh] flex flex-col">
        <DialogHeader>
          <DialogTitle>Error Logs - {plugin.name ?? plugin.folder}</DialogTitle>
          <DialogDescription>
            {plugin.errors.length}{plugin.errors.length === 10 && "+"} error{plugin.errors.length !== 1 ? 's' : ''} found in this plugin.
            {plugin.errors.length === 10 && "The most recent 10 will be shown below."}
          </DialogDescription>
        </DialogHeader>
        <div className="flex-1 overflow-y-auto p-4">
          {plugin.errors.length === 0 ? (
            <div className="flex flex-col items-center justify-center py-8 text-center">
              <AlertCircleIcon className="h-12 w-12 text-muted-foreground mb-3" />
              <p className="text-muted-foreground">No errors found</p>
            </div>
          ) : (
            <div className="space-y-3">
              {plugin.errors.map((error, index) => (
                <Card key={index} className="overflow-hidden">
                  <CardRow
                    className={cn(
                      "cursor-pointer hover:bg-accent/50 transition-colors",
                      expandedItems.has(index) && "border-b"
                    )}
                    onClick={() => toggleExpanded(index)}
                  >
                    <div className="flex items-start gap-3 flex-1">
                      <div className="mt-0.5">
                        {expandedItems.has(index) ? (
                          <ChevronDownIcon className="h-4 w-4 text-muted-foreground" />
                        ) : (
                          <ChevronRightIcon className="h-4 w-4 text-muted-foreground" />
                        )}
                      </div>
                      <div className="flex-1 min-w-0">
                        <p className="text-sm font-medium text-destructive break-words overflow-wrap-anywhere">
                          {error.error_message}
                        </p>
                        <div className="flex items-start gap-4 mt-4 flex-wrap">
                          <div className="flex items-start gap-1.5 text-xs text-muted-foreground min-w-0">
                            <TimerIcon className="h-3 w-3 flex-shrink-0" />
                            <span className="font-mono break-all">
                              <DateTime date={error.occurred_at} />
                            </span>
                          </div>
                        </div>
                        <div className="flex items-start gap-4 mt-2 flex-wrap">
                          <div className="flex items-start gap-1.5 text-xs text-muted-foreground min-w-0">
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
                    <CardContent className="p-4 bg-muted/30">
                      <div className="space-y-2">
                        <p className="text-xs font-medium text-muted-foreground uppercase tracking-wider">
                          Stack Trace
                        </p>
                        <pre className="text-xs font-mono bg-background rounded-md p-3 overflow-x-auto whitespace-pre-wrap break-all">
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
