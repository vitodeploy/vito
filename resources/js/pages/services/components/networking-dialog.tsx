import { type ReactNode, useEffect, useState } from 'react';
import { useQuery, useQueryClient } from '@tanstack/react-query';
import axios from 'axios';
import { toast } from 'sonner';
import { AlertCircleIcon, CopyIcon, LoaderCircleIcon, RefreshCwIcon, Trash2Icon, TriangleAlertIcon } from 'lucide-react';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { PasswordInput } from '@/components/ui/password-input';
import { Skeleton } from '@/components/ui/skeleton';
import DateTime from '@/components/date-time';
import { SOCKET_EVENT } from '@/stores/socket-store';
import { Service } from '@/types/service';

type NetworkingUnsupported = {
  supported: false;
};

type NetworkingSupported = {
  supported: true;
  pending: boolean;
  failed: boolean;
  enabled: boolean;
  managed: boolean;
  port: number;
  effective: boolean | null;
  checked_at: string | null;
  secret?: string | null;
  uses_password?: boolean;
  requires_remote_users?: boolean;
};

type NetworkingResponse = NetworkingUnsupported | NetworkingSupported;

const networkingQueryKey = (service: Service) => ['services.networking', service.server_id, service.id];

function StateRow({ label, children }: { label: string; children: ReactNode }) {
  return (
    <div className="flex items-center justify-between gap-4">
      <span className="text-muted-foreground text-sm">{label}</span>
      {children}
    </div>
  );
}

function SecretField({
  secret,
  busy,
  onRegenerate,
  onRemove,
}: {
  secret: string;
  busy: boolean;
  onRegenerate: () => void;
  onRemove: (() => void) | null;
}) {
  const copy = () => {
    if (!navigator.clipboard) {
      toast.error('The clipboard is not available in this browser');
      return;
    }

    navigator.clipboard
      .writeText(secret)
      .then(() => toast.success('Password copied to clipboard'))
      .catch(() => toast.error('Failed to copy the password'));
  };

  return (
    <div className="flex flex-col gap-2">
      <Label htmlFor="networking-secret">Password</Label>
      <div className="flex items-center gap-2">
        <div className="flex-1">
          <PasswordInput id="networking-secret" readOnly value={secret} className="font-mono" />
        </div>
        <Button type="button" variant="outline" size="icon" onClick={copy} aria-label="Copy password">
          <CopyIcon />
        </Button>
      </div>
      <div className="flex items-center gap-2">
        <Button type="button" variant="outline" size="sm" disabled={busy} onClick={onRegenerate}>
          <RefreshCwIcon />
          Regenerate
        </Button>
        {onRemove && (
          <Button type="button" variant="outline" size="sm" disabled={busy} onClick={onRemove}>
            <Trash2Icon />
            Remove
          </Button>
        )}
      </div>
      <p className="text-muted-foreground text-xs">
        {onRemove
          ? 'Both restart the service. Every client must be updated to the new password, or to no password at all.'
          : 'Regenerating restarts the service - every client must be updated to the new password. The password can only be removed while the service is local only.'}
      </p>
    </div>
  );
}

export default function ServiceNetworkingDialog({
  open,
  onOpenChange,
  service,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  service: Service;
}) {
  const queryClient = useQueryClient();
  const [submitting, setSubmitting] = useState(false);
  const [submitError, setSubmitError] = useState<string | null>(null);

  const query = useQuery<NetworkingResponse>({
    queryKey: networkingQueryKey(service),
    queryFn: async () => {
      const response = await axios.get(route('services.networking', { server: service.server_id, service: service.id }));
      return response.data;
    },
    retry: false,
    enabled: open,
    gcTime: 0,
    refetchOnWindowFocus: false,
    refetchInterval: (currentQuery) => {
      const data = currentQuery.state.data;
      return data?.supported && data.pending ? 3000 : false;
    },
  });

  const data = query.data;
  const details: NetworkingSupported | null = data && data.supported ? data : null;
  const pending = details?.pending ?? false;
  const usesPassword = details?.uses_password === true;
  const networked = details === null ? false : (details.effective ?? details.enabled);
  const unknown = details !== null && !pending && details.effective === null;
  const neverChecked = unknown && details.checked_at === null;

  useEffect(() => {
    const handler = (event: CustomEvent<{ type: string; data: Record<string, unknown> }>) => {
      const { type, data: payload } = event.detail;
      const matchesService = type === 'service.updated' && payload?.id === service.id;
      const matchesServer = type === 'service.refreshed' && payload?.server_id === service.server_id;

      if (matchesService || matchesServer) {
        void queryClient.invalidateQueries({ queryKey: networkingQueryKey(service) });
      }
    };

    window.addEventListener(SOCKET_EVENT, handler);

    return () => window.removeEventListener(SOCKET_EVENT, handler);
  }, [queryClient, service]);

  const params = { server: service.server_id, service: service.id };

  const submit = async (request: () => Promise<unknown>) => {
    setSubmitting(true);
    setSubmitError(null);

    try {
      await request();
      await queryClient.invalidateQueries({ queryKey: networkingQueryKey(service) });
    } catch (error) {
      if (axios.isAxiosError(error)) {
        const payload = error.response?.data as { message?: string; errors?: Record<string, string[]> } | undefined;
        const messages = payload?.errors ? Object.values(payload.errors).flat() : [];
        setSubmitError(messages.length > 0 ? messages.join(' ') : (payload?.message ?? 'Failed to update networking.'));
      } else {
        setSubmitError('Failed to update networking.');
      }
    } finally {
      setSubmitting(false);
    }
  };

  const toggle = (action: 'enable' | 'disable') => submit(() => axios.post(route(`services.networking.${action}`, params)));

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>
            Networking for <span className="capitalize">{service.name}</span>
          </DialogTitle>
          <DialogDescription>
            Networking opens this service on all IPv4 interfaces (0.0.0.0) so other servers can reach it. Access is only gated by authentication and
            your firewall.
          </DialogDescription>
        </DialogHeader>

        <div className="flex flex-col gap-4 p-4">
          {query.isLoading && (
            <>
              <Skeleton className="h-5 w-full" />
              <Skeleton className="h-5 w-full" />
              <Skeleton className="h-16 w-full" />
            </>
          )}

          {query.isError && (
            <Alert variant="destructive">
              <AlertCircleIcon />
              <AlertTitle>Could not load networking state</AlertTitle>
              <AlertDescription>
                {(axios.isAxiosError(query.error) ? query.error.response?.data?.message : null) ?? 'Please try again in a moment.'}
              </AlertDescription>
            </Alert>
          )}

          {data && !data.supported && (
            <Alert>
              <AlertCircleIcon />
              <AlertTitle>Not supported</AlertTitle>
              <AlertDescription>This service does not support the networking toggle.</AlertDescription>
            </Alert>
          )}

          {details && (
            <>
              <div className="flex flex-col gap-2">
                <StateRow label="Networking">
                  {pending ? (
                    <Badge variant="gray">Applying…</Badge>
                  ) : unknown ? (
                    <Badge variant="gray">Unknown</Badge>
                  ) : (
                    <Badge variant={networked ? 'success' : 'gray'}>{networked ? 'Listening on all interfaces' : 'Local only'}</Badge>
                  )}
                </StateRow>
                <StateRow label="Default port">
                  <span className="font-mono text-sm">{details.port}</span>
                </StateRow>
              </div>

              {pending && (
                <Alert>
                  <LoaderCircleIcon className="animate-spin" />
                  <AlertTitle>Applying the change</AlertTitle>
                  <AlertDescription>
                    Vito is updating the configuration and restarting the service. This dialog updates automatically.
                  </AlertDescription>
                </Alert>
              )}

              {details.failed && !pending && (
                <Alert variant="destructive">
                  <TriangleAlertIcon />
                  <AlertTitle>The last change failed</AlertTitle>
                  <AlertDescription>
                    Vito could not apply the last networking change and reverted the configuration. Check the server logs for details, then try again.
                  </AlertDescription>
                </Alert>
              )}

              {unknown && (
                <Alert>
                  <TriangleAlertIcon />
                  <AlertTitle>Refresh the services first</AlertTitle>
                  <AlertDescription>
                    <p>
                      {neverChecked ? (
                        <>Vito hasn&apos;t checked this service&apos;s live state yet.</>
                      ) : (
                        <>
                          Vito couldn&apos;t read this service&apos;s live state on the last check (<DateTime date={details.checked_at as string} />
                          ). Check that the service is healthy first.
                        </>
                      )}{' '}
                      Close this dialog and use Refresh on the Services page - networking can&apos;t be changed until Vito knows the current state.
                    </p>
                  </AlertDescription>
                </Alert>
              )}

              {usesPassword && !pending && !networked && !details.secret && (
                <Alert variant="destructive">
                  <TriangleAlertIcon />
                  <AlertTitle>A password will be set</AlertTitle>
                  <AlertDescription>
                    Vito generates a password when networking is enabled. Every existing client on this server (apps, workers, queues) must be updated
                    to authenticate with it, or it will fail with NOAUTH.
                  </AlertDescription>
                </Alert>
              )}

              {usesPassword && !pending && !details.failed && !networked && details.secret && (
                <Alert variant="destructive">
                  <TriangleAlertIcon />
                  <AlertTitle>A password has been generated</AlertTitle>
                  <AlertDescription>
                    Networking is off. If it was ever enabled, this password is still in effect on the server and every client must keep
                    authenticating with it. Enabling networking applies it again.
                  </AlertDescription>
                </Alert>
              )}

              {usesPassword && !pending && networked && (
                <Alert>
                  <AlertCircleIcon />
                  <AlertTitle>Disabling keeps the password</AlertTitle>
                  <AlertDescription>
                    The service goes back to local only, but the password remains set - local clients keep authenticating with it.
                  </AlertDescription>
                </Alert>
              )}

              {usesPassword && details.secret && (
                <SecretField
                  secret={details.secret}
                  busy={submitting || pending}
                  onRegenerate={() => void submit(() => axios.post(route('services.networking.secret.regenerate', params)))}
                  onRemove={networked ? null : () => void submit(() => axios.delete(route('services.networking.secret.destroy', params)))}
                />
              )}

              {details.requires_remote_users === true && (
                <Alert>
                  <AlertCircleIcon />
                  <AlertTitle>Remote database users are required</AlertTitle>
                  <AlertDescription>Remote servers need a database user with remote host (%) to connect.</AlertDescription>
                </Alert>
              )}

              {details.requires_remote_users === false && (
                <Alert>
                  <AlertCircleIcon />
                  <AlertTitle>Authentication</AlertTitle>
                  <AlertDescription>Remote connections authenticate with database user passwords.</AlertDescription>
                </Alert>
              )}
            </>
          )}

          {submitError && (
            <Alert variant="destructive">
              <AlertCircleIcon />
              <AlertDescription>{submitError}</AlertDescription>
            </Alert>
          )}
        </div>

        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Close</Button>
          </DialogClose>
          {details && (
            <Button
              variant={networked ? 'destructive' : 'default'}
              disabled={submitting || pending || unknown}
              onClick={() => toggle(networked ? 'disable' : 'enable')}
            >
              {(submitting || pending) && <LoaderCircleIcon className="animate-spin" />}
              {networked ? 'Disable networking' : 'Enable networking'}
            </Button>
          )}
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
