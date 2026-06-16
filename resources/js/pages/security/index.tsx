import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useCallback, useState } from 'react';
import { Server } from '@/types/server';
import { Service } from '@/types/service';
import { AutoUpdateState, PasswordAuthState, RootLoginState, SecurityScore } from '@/types/security';
import ServerLayout from '@/layouts/server/layout';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import Container from '@/components/container';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import { Badge } from '@/components/ui/badge';
import InputError from '@/components/ui/input-error';
import { useDialog } from '@/hooks/use-dialog';
import { useSocketListener } from '@/hooks/use-socket-events';
import { InfoIcon, LoaderCircleIcon, RefreshCwIcon, TriangleAlertIcon } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Progress } from '@/components/ui/progress';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableRow } from '@/components/ui/table';
import { cn } from '@/lib/utils';
import ScheduleBuilder, { buildSchedule, parseSchedule } from '@/pages/security/components/schedule-builder';

function serviceStatusLabel(status: string): string {
  return status === 'ready' ? 'active' : status;
}

function ControlBadge({ status, color, secure }: { status: string; color: 'gray' | 'success' | 'info' | 'warning' | 'danger'; secure: boolean }) {
  if (status === 'disabled') {
    return null;
  }
  if (status === 'active' && !secure) {
    return null;
  }
  return (
    <Badge variant={color} aria-busy={status === 'updating'}>
      {status === 'updating' && <LoaderCircleIcon className="animate-spin" />}
      {status}
    </Badge>
  );
}

function AutoUpdateCard({ server, autoUpdate }: { server: number; autoUpdate: AutoUpdateState }) {
  const [parsed, setParsed] = useState(parseSchedule(autoUpdate.schedule));
  const form = useForm<{ enabled: boolean; schedule: string }>({
    enabled: autoUpdate.enabled,
    schedule: autoUpdate.schedule ?? buildSchedule(parseSchedule(null)),
  });

  const save = () => form.post(route('security.auto-update', { server }), { preserveScroll: true });

  return (
    <Card>
      <CardHeader>
        <div className="flex items-center justify-between gap-2">
          <div className="space-y-1.5">
            <CardTitle className="flex items-center gap-2">
              Automatic updates
              {autoUpdate.enabled && <Badge variant="success">active</Badge>}
            </CardTitle>
            <CardDescription>Vito applies package updates to this server on a schedule (times are UTC).</CardDescription>
          </div>
          <Switch checked={form.data.enabled} onCheckedChange={(value) => form.setData('enabled', value)} aria-label="Enable automatic updates" />
        </div>
      </CardHeader>
      {form.data.enabled && (
        <CardContent className="flex flex-col gap-4 p-4">
          <ScheduleBuilder
            value={parsed}
            onChange={(next) => {
              setParsed(next);
              form.setData('schedule', buildSchedule(next));
            }}
          />
          <InputError message={form.errors.schedule} />
          <p className="text-muted-foreground text-xs">
            Disabling automatic updates stops future runs only; it does not undo updates already applied.
          </p>
        </CardContent>
      )}
      <CardFooter className="justify-end">
        <Button onClick={save} disabled={form.processing}>
          {form.processing && <LoaderCircleIcon className="animate-spin" />}
          Save
        </Button>
      </CardFooter>
    </Card>
  );
}

function PasswordAuthCard({ server, passwordAuth }: { server: number; passwordAuth: PasswordAuthState }) {
  const dialog = useDialog();
  const updating = passwordAuth.status === 'updating';
  const secure = !passwordAuth.enabled;
  const drift = !updating && passwordAuth.detected != null && passwordAuth.detected !== passwordAuth.enabled;

  const toggle = (nextSecure: boolean) => {
    dialog.confirm.open({
      title: nextSecure ? 'Disable password authentication' : 'Allow password authentication',
      description: nextSecure
        ? 'Only SSH key authentication will be allowed. Make sure your key works — any password-only users will be locked out.'
        : 'Password logins will be allowed again over SSH.',
      variant: nextSecure ? 'destructive' : 'default',
      confirmLabel: nextSecure ? 'Disable' : 'Allow',
      method: 'post',
      url: route('security.password-auth', { server }),
      data: { enabled: !nextSecure },
    });
  };

  return (
    <Card>
      <CardHeader>
        <div className="flex items-center justify-between gap-2">
          <div className="space-y-1.5">
            <CardTitle className="flex items-center gap-2">
              Disable password authentication
              <ControlBadge status={passwordAuth.status} color={passwordAuth.status_color} secure={secure} />
              {drift && <Badge variant="warning">drift detected</Badge>}
            </CardTitle>
            <CardDescription>When on, SSH access requires a key and password logins are refused (global default only).</CardDescription>
          </div>
          <Switch checked={secure} disabled={updating} onCheckedChange={toggle} aria-label="Disable password authentication" />
        </div>
      </CardHeader>
    </Card>
  );
}

function RootLoginCard({ server, rootLogin }: { server: number; rootLogin: RootLoginState }) {
  const dialog = useDialog();
  const updating = rootLogin.status === 'updating';
  const secure = !rootLogin.enabled;
  const drift = !updating && rootLogin.detected != null && rootLogin.detected !== rootLogin.enabled;

  const toggle = (nextSecure: boolean) => {
    dialog.confirm.open({
      title: nextSecure ? 'Disable root SSH login' : 'Allow root SSH login',
      description: nextSecure
        ? 'Root SSH login will be refused. Vito connects as the deploy user and uses sudo, so this is safe.'
        : 'Root will be allowed to log in over SSH again.',
      variant: nextSecure ? 'destructive' : 'default',
      confirmLabel: nextSecure ? 'Disable' : 'Allow',
      method: 'post',
      url: route('security.root-login', { server }),
      data: { enabled: !nextSecure },
    });
  };

  return (
    <Card>
      <CardHeader>
        <div className="flex items-center justify-between gap-2">
          <div className="space-y-1.5">
            <CardTitle className="flex items-center gap-2">
              Disable root SSH login
              <ControlBadge status={rootLogin.status} color={rootLogin.status_color} secure={secure} />
              {drift && <Badge variant="warning">drift detected</Badge>}
            </CardTitle>
            <CardDescription>
              {rootLogin.manageable
                ? 'When on, the root user cannot log in over SSH.'
                : 'Vito currently connects to this server as root; switch to a non-root SSH user before disabling root login.'}
            </CardDescription>
          </div>
          <Switch checked={secure} disabled={updating || !rootLogin.manageable} onCheckedChange={toggle} aria-label="Disable root SSH login" />
        </div>
      </CardHeader>
    </Card>
  );
}

function Fail2banCard({ server, fail2ban }: { server: number; fail2ban: Service | null }) {
  const dialog = useDialog();
  const installing = fail2ban?.status === 'installing' || fail2ban?.status === 'uninstalling';

  return (
    <Card>
      <CardHeader>
        <div className="flex items-center justify-between gap-2">
          <div className="space-y-1.5">
            <CardTitle className="flex items-center gap-2">
              Fail2ban
              {fail2ban && (
                <Badge variant={fail2ban.status_color} aria-busy={installing}>
                  {installing && <LoaderCircleIcon className="animate-spin" />}
                  {serviceStatusLabel(fail2ban.status)}
                </Badge>
              )}
            </CardTitle>
            <CardDescription>Bans IP addresses with too many failed SSH login attempts.</CardDescription>
          </div>
          <div className="flex items-center gap-2">
            {fail2ban ? (
              <>
                <Button variant="outline" disabled={installing} onClick={() => dialog.fail2banForm.open({ serverId: server, fail2ban })}>
                  Configure
                </Button>
                <Button
                  variant="destructive"
                  disabled={installing}
                  onClick={() =>
                    dialog.confirm.open({
                      title: 'Uninstall Fail2ban',
                      description: 'This removes Fail2ban and its configuration from the server, and from the Services list.',
                      variant: 'destructive',
                      confirmLabel: 'Uninstall',
                      method: 'delete',
                      url: route('security.fail2ban.destroy', { server }),
                    })
                  }
                >
                  Uninstall
                </Button>
              </>
            ) : (
              <Button onClick={() => dialog.fail2banForm.open({ serverId: server, fail2ban: null })}>Install</Button>
            )}
          </div>
        </div>
      </CardHeader>
      {fail2ban && (
        <CardContent className="p-4">
          <p className="text-muted-foreground text-xs">View banned IPs and activity in Service logs.</p>
        </CardContent>
      )}
    </Card>
  );
}

function ScoreCard({ score }: { score: SecurityScore }) {
  const textColor = score.score >= 80 ? 'text-success' : score.score >= 40 ? 'text-warning' : 'text-destructive';
  const barColor =
    score.score >= 80
      ? '[&>[data-slot=progress-indicator]]:bg-success'
      : score.score >= 40
        ? '[&>[data-slot=progress-indicator]]:bg-warning'
        : '[&>[data-slot=progress-indicator]]:bg-destructive';

  return (
    <Card>
      <CardContent className="flex flex-col gap-3 p-4">
        <div className="flex items-center justify-between">
          <CardTitle className="flex items-center gap-1.5">
            Security score
            <Tooltip>
              <TooltipTrigger asChild>
                <button type="button" className="text-muted-foreground hover:text-foreground" aria-label="About the security score">
                  <InfoIcon className="size-4" />
                </button>
              </TooltipTrigger>
              <TooltipContent className="max-w-xs">
                This score only reflects the hardening features Vito manages. It is not an indicative or complete security rating for your server.
              </TooltipContent>
            </Tooltip>
          </CardTitle>
          <div className="flex items-baseline gap-1">
            <span className={`text-2xl font-semibold ${textColor}`}>{score.score}</span>
            <span className="text-muted-foreground text-sm">/ 100</span>
          </div>
        </div>
        <Progress value={score.score} className={cn('bg-primary/10 dark:bg-primary/20', barColor)} />
        <Table>
          <TableBody>
            {score.checks.map((check) => (
              <TableRow key={check.key}>
                <TableCell className="py-2">{check.label}</TableCell>
                <TableCell className="w-0 py-2 text-right">
                  <Badge variant={check.passed ? 'success' : 'gray'}>{check.passed ? 'applied' : 'not applied'}</Badge>
                </TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  );
}

function FirewallCard({ server, firewall }: { server: number; firewall: Service | null }) {
  const installing = firewall?.status === 'installing' || firewall?.status === 'uninstalling';
  return (
    <Card>
      <CardHeader>
        <div className="flex items-center justify-between gap-2">
          <div className="space-y-1.5">
            <CardTitle className="flex items-center gap-2">
              Firewall
              {firewall && (
                <Badge variant={firewall.status_color} aria-busy={installing}>
                  {installing && <LoaderCircleIcon className="animate-spin" />}
                  {serviceStatusLabel(firewall.status)}
                </Badge>
              )}
            </CardTitle>
            <CardDescription>A UFW firewall controls which ports accept inbound connections.</CardDescription>
          </div>
          {firewall ? (
            <Button variant="outline" asChild>
              <Link href={route('firewall', { server })}>Manage rules</Link>
            </Button>
          ) : (
            <Button disabled={installing} onClick={() => router.post(route('security.firewall.install', { server }), {}, { preserveScroll: true })}>
              Install
            </Button>
          )}
        </div>
      </CardHeader>
    </Card>
  );
}

export default function Security() {
  const page = usePage<{
    server: Server;
    score: SecurityScore;
    autoUpdate: AutoUpdateState;
    passwordAuth: PasswordAuthState;
    rootLogin: RootLoginState;
    fail2ban: Service | null;
    firewall: Service | null;
  }>();

  const serverId = page.props.server.id;
  const [checking, setChecking] = useState(false);

  useSocketListener(
    useCallback(
      (event) => {
        const data = event.data;
        const isObject = !!data && typeof data === 'object' && !Array.isArray(data);
        if (event.type === 'security.updated' && isObject && (data as { server_id?: number }).server_id === serverId) {
          router.reload({ only: ['passwordAuth', 'rootLogin', 'autoUpdate', 'fail2ban', 'firewall', 'score'] });
        } else if (event.type?.startsWith('service.') && isObject && (data as { server_id?: number }).server_id === serverId) {
          router.reload({ only: ['fail2ban', 'firewall', 'score'] });
        }
      },
      [serverId],
    ),
  );

  const check = () => {
    setChecking(true);
    router.post(route('security.check', { server: serverId }), {}, { preserveScroll: true, onFinish: () => setChecking(false) });
  };

  return (
    <ServerLayout>
      <Head title={`Security - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Security" description="Harden this server: automatic updates, Fail2ban, SSH access, and the SSH port." />
          <Button variant="outline" onClick={check} disabled={checking}>
            {checking ? <LoaderCircleIcon className="animate-spin" /> : <RefreshCwIcon />}
            <span className="hidden lg:block">Check status</span>
          </Button>
        </HeaderContainer>

        <div className="flex flex-col gap-4 [&_[data-slot=card-header]]:border-b-0">
          <ScoreCard score={page.props.score} />

          <Separator className="my-2" />

          <Heading title="Actions" description="Apply, reconfigure, or undo each hardening measure." />

          <Alert>
            <TriangleAlertIcon className="text-warning" />
            <AlertDescription>
              These settings change how your server is accessed and secured. Make sure you can reach your provider's recovery console before applying
              them, so you can roll back if anything goes wrong.
            </AlertDescription>
          </Alert>

          <AutoUpdateCard
            key={`${page.props.autoUpdate.enabled}-${page.props.autoUpdate.schedule ?? ''}`}
            server={serverId}
            autoUpdate={page.props.autoUpdate}
          />
          <FirewallCard server={serverId} firewall={page.props.firewall} />
          <Fail2banCard server={serverId} fail2ban={page.props.fail2ban} />
          <PasswordAuthCard server={serverId} passwordAuth={page.props.passwordAuth} />
          <RootLoginCard server={serverId} rootLogin={page.props.rootLogin} />
        </div>
      </Container>
    </ServerLayout>
  );
}
