import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useCallback, useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';
import { CheckIcon, CopyIcon, DownloadIcon, LoaderCircleIcon, TriangleAlertIcon } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { toast } from 'sonner';
import axios from 'axios';

function SectionHeader({ title, hint, children }: { title: string; hint: string; children?: React.ReactNode }) {
  return (
    <div className="flex items-start justify-between gap-4">
      <div className="space-y-0.5">
        <p className="text-sm leading-none font-medium">{title}</p>
        <p className="text-muted-foreground text-xs">{hint}</p>
      </div>
      {children && <div className="flex shrink-0 items-center gap-1">{children}</div>}
    </div>
  );
}

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
  const [privateKey, setPrivateKey] = useState<string | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string>('');
  const [concealing, setConcealing] = useState(false);
  const [copied, setCopied] = useState<string>('');

  const load = useCallback(
    (signal?: AbortSignal) => {
      setLoading(true);
      setError('');

      return axios
        .get(route('networks.peers.config', { network: networkId, networkPeer: peerId }), { signal })
        .then((response) => {
          setConfig(response.data.config);
          setPrivateKey(response.data.private_key ?? null);
        })
        .catch((e) => {
          if (axios.isCancel(e)) return;
          setError('Could not load the peer configuration.');
        })
        .finally(() => setLoading(false));
    },
    [networkId, peerId],
  );

  useEffect(() => {
    if (!open) return;

    const controller = new AbortController();
    load(controller.signal);

    return () => controller.abort();
  }, [open, load]);

  const copy = (value: string, key: string, label: string) => {
    navigator.clipboard
      .writeText(value)
      .then(() => {
        setCopied(key);
        toast.success(`${label} copied`);
        setTimeout(() => setCopied(''), 2000);
      })
      .catch(() => toast.error('Could not copy to clipboard'));
  };

  const download = () => {
    const blob = new Blob([config], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `${name}.conf`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
  };

  const conceal = () => {
    setConcealing(true);
    router.post(
      route('networks.peers.conceal', { network: networkId, networkPeer: peerId }),
      {},
      {
        preserveScroll: true,
        // Reload rather than close: the configuration stays available, so show the user
        // what it looks like now that the private key is gone.
        onSuccess: () => load(),
        onError: () => toast.error('Could not conceal the private key'),
        onFinish: () => setConcealing(false),
      },
    );
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-xl" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Peer configuration</DialogTitle>
          <DialogDescription>Import this into the WireGuard client on {name}.</DialogDescription>
        </DialogHeader>

        <div className="max-h-[65vh] space-y-6 overflow-y-auto p-4">
          {loading && (
            <div className="text-muted-foreground flex items-center gap-2 py-6 text-sm">
              <LoaderCircleIcon className="size-4 animate-spin" />
              Loading configuration…
            </div>
          )}

          {!loading && error && (
            <Alert variant="destructive">
              <TriangleAlertIcon />
              <AlertDescription>
                <p>{error}</p>
              </AlertDescription>
            </Alert>
          )}

          {!loading && !error && (
            <>
              {privateKey && (
                <section className="space-y-2">
                  <SectionHeader title="Private key" hint="Shown once - Vito deletes it when you confirm below.">
                    <Button type="button" variant="outline" size="sm" onClick={() => copy(privateKey, 'key', 'Private key')}>
                      {copied === 'key' ? <CheckIcon /> : <CopyIcon />}
                      Copy
                    </Button>
                  </SectionHeader>
                  <pre className="bg-muted/50 overflow-x-auto rounded-md border p-3 font-mono text-xs">{privateKey}</pre>
                </section>
              )}

              {!privateKey && (
                <Alert>
                  <TriangleAlertIcon />
                  <AlertDescription>
                    <p>
                      {byo
                        ? 'This peer uses a key you provided, so Vito never had its private key.'
                        : 'This private key was concealed and cannot be shown again.'}{' '}
                      Set the <span className="font-mono">PrivateKey</span> line below to the peer's own private key
                      {byo ? '.' : ', or regenerate the peer keys to issue a new one.'}
                    </p>
                  </AlertDescription>
                </Alert>
              )}

              <section className="space-y-2">
                <SectionHeader title="Configuration" hint="Updates as servers join or leave - reopen this any time.">
                  <Button type="button" variant="outline" size="sm" onClick={() => copy(config, 'config', 'Configuration')}>
                    {copied === 'config' ? <CheckIcon /> : <CopyIcon />}
                    Copy
                  </Button>
                  <Button type="button" variant="outline" size="sm" onClick={download}>
                    <DownloadIcon />
                    .conf
                  </Button>
                </SectionHeader>
                <pre className="bg-muted/50 max-h-64 overflow-auto rounded-md border p-3 font-mono text-xs leading-relaxed">{config}</pre>
              </section>
            </>
          )}
        </div>

        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Close</Button>
          </DialogClose>
          {privateKey && !error && (
            <Button disabled={loading || concealing} onClick={conceal}>
              {concealing && <LoaderCircleIcon className="animate-spin" />}
              I've saved the private key
            </Button>
          )}
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
