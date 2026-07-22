import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';
import { CopyIcon, DownloadIcon, LoaderCircleIcon } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { toast } from 'sonner';
import axios from 'axios';

export default function PeerConfigDialog({
  open,
  onOpenChange,
  networkId,
  peerId,
  byo,
  name,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  networkId: number;
  peerId: number;
  byo: boolean;
  name: string;
}) {
  const [config, setConfig] = useState<string>('');
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string>('');
  const [concealing, setConcealing] = useState(false);

  useEffect(() => {
    if (!open) {
      return;
    }

    setLoading(true);
    setError('');
    axios
      .get(route('networks.peers.config', { network: networkId, networkPeer: peerId }))
      .then((response) => setConfig(response.data.config))
      .catch((e) => {
        setError(
          e?.response?.status === 410
            ? 'This config has already been concealed. Regenerate the keys to view it again.'
            : 'Could not load the peer configuration.',
        );
      })
      .finally(() => setLoading(false));
  }, [open, networkId, peerId]);

  const copy = () => {
    navigator.clipboard
      .writeText(config)
      .then(() => toast.success('Copied to clipboard'))
      .catch(() => toast.error('Could not copy to clipboard'));
  };

  const download = () => {
    const blob = new Blob([config], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `${name}.conf`;
    link.click();
    URL.revokeObjectURL(url);
  };

  const conceal = () => {
    setConcealing(true);
    router.post(
      route('networks.peers.conceal', { network: networkId, networkPeer: peerId }),
      {},
      {
        preserveScroll: true,
        onSuccess: () => onOpenChange(false),
        onFinish: () => setConcealing(false),
      },
    );
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Peer configuration</DialogTitle>
          <DialogDescription>Import this into the WireGuard client on {name}.</DialogDescription>
        </DialogHeader>

        <div className="space-y-3 p-4">
          {loading && (
            <div className="text-muted-foreground flex items-center gap-2 text-sm">
              <LoaderCircleIcon className="size-4 animate-spin" />
              Loading configuration…
            </div>
          )}

          {!loading && error && (
            <Alert variant="destructive">
              <AlertDescription>{error}</AlertDescription>
            </Alert>
          )}

          {!loading && !error && (
            <>
              {byo && (
                <Alert>
                  <AlertDescription>
                    Replace <code>REPLACE_WITH_YOUR_PRIVATE_KEY</code> with the private key belonging to the public key you provided.
                  </AlertDescription>
                </Alert>
              )}
              <pre className="bg-muted/50 max-h-72 overflow-auto rounded-md border p-3 font-mono text-xs whitespace-pre-wrap">{config}</pre>
              <div className="flex gap-2">
                <Button type="button" variant="outline" size="sm" onClick={copy}>
                  <CopyIcon /> Copy
                </Button>
                <Button type="button" variant="outline" size="sm" onClick={download}>
                  <DownloadIcon /> Download .conf
                </Button>
              </div>
            </>
          )}
        </div>

        <DialogFooter>
          {byo || error ? (
            <DialogClose asChild>
              <Button variant="outline">Close</Button>
            </DialogClose>
          ) : (
            <>
              <DialogClose asChild>
                <Button variant="outline">Close</Button>
              </DialogClose>
              <Button variant="destructive" disabled={loading || concealing} onClick={conceal}>
                {concealing && <LoaderCircleIcon className="animate-spin" />}
                I've saved this config
              </Button>
            </>
          )}
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
