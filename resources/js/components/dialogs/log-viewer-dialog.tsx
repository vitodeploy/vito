import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import LogOutput from '@/components/log-output';
import { useLogContent } from '@/hooks/use-log-content';

type LogViewerDialogProps = {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  serverId: number;
  logId: number;
  title: string;
};

export default function LogViewerDialog({ open, onOpenChange, serverId, logId, title }: LogViewerDialogProps) {
  const { content, isLoading, error } = useLogContent({ serverId, logId, enabled: open });

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-5xl" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>{title}</DialogTitle>
          <DialogDescription className="sr-only">Log contents</DialogDescription>
        </DialogHeader>
        <LogOutput>
          <>
            {isLoading && 'Loading...'}
            {error && <div className="text-destructive">Error: {error}</div>}
            {content && !error && content}
          </>
        </LogOutput>
        <DialogFooter>
          <a href={route('logs.download', { server: serverId, log: logId })} target="_blank" rel="noopener noreferrer">
            <Button variant="outline">Download</Button>
          </a>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
