import AdminLayout from '@/layouts/admin/layout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardRow, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Form, FormField, FormFields } from '@/components/ui/form';
import InputError from '@/components/ui/input-error';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import CopyableField from '@/components/copyable-field';
import { AlertTriangleIcon, ExternalLinkIcon, GithubIcon, LoaderCircleIcon, RefreshCcwIcon, Trash2Icon } from 'lucide-react';
import { FormEvent } from 'react';
import { SourceControl } from '@/types/source-control';
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
import { useState } from 'react';

type GithubAppData = {
  app_id: number;
  app_slug: string;
  name: string;
  html_url: string | null;
  created_at: string;
};

type Manifest = {
  manifest: string;
  submit_url: string;
};

type ManualSetup = {
  create_url: string;
  webhook_url: string;
  homepage_url: string;
  callback_url: string;
  setup_url: string;
  webhook_secret: string | null;
};

type PageProps = {
  githubApp: GithubAppData | null;
  manifest: Manifest;
  manualSetup: ManualSetup;
  installPath: string | null;
  installations: SourceControl[];
  localUrlWarning: boolean;
};

export default function GithubAppIndex() {
  const page = usePage<PageProps>();
  const { githubApp, manifest, manualSetup, installPath, installations, localUrlWarning } = page.props;

  return (
    <AdminLayout>
      <Head title="GitHub App" />
      <Container className="max-w-5xl">
        <Heading
          title="GitHub App"
          description="Register a GitHub App for this Vito instance. Organizations install it to connect their repositories."
        />

        {localUrlWarning && (
          <Alert>
            <AlertTriangleIcon />
            <AlertTitle>Vito is not publicly reachable</AlertTitle>
            <AlertDescription>
              Webhook events from GitHub cannot reach this instance. Installs and uninstalls will be detected on the 4-hour sync (or by clicking the
              Sync button below).
            </AlertDescription>
          </Alert>
        )}

        {!githubApp ? <CreateAppCard manifest={manifest} manualSetup={manualSetup} /> : <ConfiguredCard app={githubApp} installPath={installPath} />}

        {githubApp && (
          <Card className="overflow-hidden">
            <CardHeader>
              <CardTitle>Connected installations</CardTitle>
              <CardDescription>Organizations that have installed this GitHub App.</CardDescription>
            </CardHeader>
            <CardContent className="bg-background">
              {installations.length === 0 ? (
                <div className="text-muted-foreground p-4 text-sm">No installations yet. Install the app on a GitHub organization to begin.</div>
              ) : (
                installations.map((sc, index) => (
                  <div key={sc.id}>
                    <CardRow>
                      <div className="flex items-center gap-3">
                        <GithubIcon className="h-4 w-4" />
                        <span className="font-medium">{sc.github_app?.account_login || sc.name}</span>
                        <Badge variant="outline">{sc.github_app?.account_type || 'Account'}</Badge>
                        {sc.global ? <Badge variant="success">global</Badge> : <Badge variant="danger">project</Badge>}
                      </div>
                      {sc.github_app?.html_url && (
                        <a href={sc.github_app.html_url} target="_blank" rel="noopener noreferrer">
                          <Button variant="outline" size="sm">
                            <ExternalLinkIcon />
                            Manage on GitHub
                          </Button>
                        </a>
                      )}
                    </CardRow>
                    {index < installations.length - 1 && <Separator />}
                  </div>
                ))
              )}
            </CardContent>
          </Card>
        )}
      </Container>
    </AdminLayout>
  );
}

function CreateAppCard({ manifest, manualSetup }: { manifest: Manifest; manualSetup: ManualSetup }) {
  return (
    <Card>
      <CardContent>
        <div className="space-y-4 p-4">
          <div>
            <h3 className="text-sm font-medium">No GitHub App configured</h3>
            <p className="text-muted-foreground text-sm">
              Create a GitHub App for this Vito instance. The automatic flow works when Vito is reachable on a real public domain; for local
              development use the manual flow.
            </p>
          </div>

          <Tabs defaultValue="automatic">
            <TabsList>
              <TabsTrigger value="automatic">Automatic</TabsTrigger>
              <TabsTrigger value="manual">Manual</TabsTrigger>
            </TabsList>

            <TabsContent value="automatic" className="space-y-4 pt-4">
              <p className="text-muted-foreground text-sm">
                We&apos;ll send a pre-filled manifest to GitHub. GitHub creates the app, generates credentials, and redirects you back here. Requires
                a publicly reachable hostname (not <code>.test</code> / <code>.localhost</code>).
              </p>
              <form method="post" action={manifest.submit_url}>
                <input type="hidden" name="manifest" value={manifest.manifest} />
                <Button type="submit">
                  <GithubIcon />
                  Create GitHub App
                </Button>
              </form>
            </TabsContent>

            <TabsContent value="manual" className="space-y-4 pt-4">
              <ManualSetupForm manualSetup={manualSetup} />
            </TabsContent>
          </Tabs>
        </div>
      </CardContent>
    </Card>
  );
}

function StepHeader({ number, title, description }: { number: number; title: string; description: string }) {
  return (
    <div className="space-y-0.5">
      <div className="text-muted-foreground text-xs font-medium tracking-wider uppercase">Step {number}</div>
      <h4 className="font-semibold">{title}</h4>
      <p className="text-muted-foreground text-sm">{description}</p>
    </div>
  );
}

function ManualField({ label, value, hint }: { label: string; value: string; hint?: string }) {
  return (
    <div className="space-y-1">
      <Label className="text-xs font-medium">{label}</Label>
      <CopyableField value={value} />
      {hint && <p className="text-muted-foreground text-xs">{hint}</p>}
    </div>
  );
}

function ManualSetupForm({ manualSetup }: { manualSetup: ManualSetup }) {
  const form = useForm({
    app_id: '',
    name: '',
    client_id: '',
    client_secret: '',
    webhook_secret: manualSetup.webhook_secret ?? '',
    private_key: '',
    html_url: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('github-app.manual'), {
      preserveScroll: true,
      onSuccess: () => form.reset(),
    });
  };

  return (
    <div className="space-y-8">
      {/* Step 1 */}
      <section className="space-y-4">
        <StepHeader
          number={1}
          title="Create the app on GitHub"
          description="Open the link below and copy these values into the matching fields on GitHub."
        />

        <div className="space-y-6">
          <a href={manualSetup.create_url} target="_blank" rel="noopener noreferrer" className="inline-block">
            <Button variant="outline" size="sm">
              <ExternalLinkIcon />
              Open GitHub App creation page
            </Button>
          </a>

          <div className="grid gap-3 sm:grid-cols-2">
            <ManualField label="Homepage URL" value={manualSetup.homepage_url} />
            <ManualField label="Callback URL" value={manualSetup.callback_url} />
            <ManualField label="Setup URL (post installation)" value={manualSetup.setup_url} hint='Tick "Redirect on update"' />
            <ManualField label="Webhook URL" value={manualSetup.webhook_url} hint='Leave "Active" checked' />
            <div className="sm:col-span-2">
              <ManualField
                label="Webhook secret"
                value={manualSetup.webhook_secret ?? ''}
                hint="Generated for you — paste this into GitHub. The same value is pre-filled in Step 2 below."
              />
            </div>
          </div>

          <div className="bg-muted/40 space-y-2 rounded-md border p-3 text-sm">
            <div className="text-muted-foreground text-xs font-medium tracking-wider uppercase">Permissions &amp; events</div>
            <ul className="ml-5 list-disc space-y-1">
              <li>
                Repository → Contents: <strong>Read-only</strong>
              </li>
              <li>
                Repository → Metadata: <strong>Read-only</strong> (auto-selected)
              </li>
              <li>
                Subscribe to events: <strong>Push</strong>
              </li>
              <li>
                Where can this GitHub App be installed?: <strong>Any account</strong> (required for installing on orgs)
              </li>
            </ul>
            <p className="text-muted-foreground pt-1 text-xs">
              <strong>Installation</strong> and <strong>installation repositories</strong> events are delivered automatically to every GitHub App with
              a webhook URL — they aren&apos;t in the &ldquo;Subscribe to events&rdquo; list and don&apos;t need to be selected. Vito uses these to
              keep installed accounts and repositories in sync.
            </p>
          </div>

          <p className="text-muted-foreground text-sm">
            After saving, on the new app&apos;s settings page generate a <strong>Client secret</strong>, then scroll to <strong>Private keys</strong>{' '}
            and <strong>Generate a private key</strong> (downloads as a .pem file). Note the <strong>App ID</strong> from the top of the page.
          </p>
        </div>
      </section>

      <Separator />

      {/* Step 2 */}
      <section className="space-y-4">
        <StepHeader number={2} title="Paste the credentials below" description="Fill in the values GitHub gave you in Step 1, then save." />

        <Form id="github-app-manual" onSubmit={submit}>
          <FormFields>
            <div className="grid grid-cols-2 items-start gap-4">
              <FormField>
                <Label htmlFor="app_id">App ID</Label>
                <Input
                  id="app_id"
                  type="text"
                  inputMode="numeric"
                  value={form.data.app_id}
                  onChange={(e) => form.setData('app_id', e.target.value)}
                />
                <InputError message={form.errors.app_id} />
              </FormField>
              <FormField>
                <Label htmlFor="name">Display name (optional)</Label>
                <Input id="name" type="text" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                <InputError message={form.errors.name} />
              </FormField>
            </div>
            <FormField>
              <Label htmlFor="html_url">App page URL</Label>
              <Input
                id="html_url"
                type="text"
                placeholder="https://github.com/apps/your-slug"
                value={form.data.html_url}
                onChange={(e) => form.setData('html_url', e.target.value)}
              />
              <p className="text-muted-foreground text-xs">
                From the address bar on the app&apos;s settings page — e.g. https://github.com/apps/vito-yourhost.
              </p>
              <InputError message={form.errors.html_url} />
            </FormField>
            <div className="grid grid-cols-2 items-start gap-4">
              <FormField>
                <Label htmlFor="client_id">Client ID</Label>
                <Input id="client_id" type="text" value={form.data.client_id} onChange={(e) => form.setData('client_id', e.target.value)} />
                <InputError message={form.errors.client_id} />
              </FormField>
              <FormField>
                <Label htmlFor="client_secret">Client secret</Label>
                <Input
                  id="client_secret"
                  type="password"
                  value={form.data.client_secret}
                  onChange={(e) => form.setData('client_secret', e.target.value)}
                />
                <InputError message={form.errors.client_secret} />
              </FormField>
            </div>
            <FormField>
              <Label htmlFor="webhook_secret">Webhook secret</Label>
              <Input
                id="webhook_secret"
                type="password"
                value={form.data.webhook_secret}
                onChange={(e) => form.setData('webhook_secret', e.target.value)}
              />
              <p className="text-muted-foreground text-xs">
                Pre-filled with the value from Step 1. Leave as-is unless you used something different on GitHub.
              </p>
              <InputError message={form.errors.webhook_secret} />
            </FormField>
            <FormField>
              <Label htmlFor="private_key">Private key (PEM)</Label>
              <Textarea
                id="private_key"
                rows={10}
                className="font-mono text-xs"
                placeholder="-----BEGIN RSA PRIVATE KEY-----&#10;..."
                value={form.data.private_key}
                onChange={(e) => form.setData('private_key', e.target.value)}
              />
              <p className="text-muted-foreground text-xs">Paste the entire contents of the .pem file downloaded from GitHub.</p>
              <InputError message={form.errors.private_key} />
            </FormField>
            <Button type="submit" disabled={form.processing}>
              {form.processing && <LoaderCircleIcon className="animate-spin" />}
              Save GitHub App
            </Button>
          </FormFields>
        </Form>
      </section>
    </div>
  );
}

function ConfiguredCard({ app, installPath }: { app: GithubAppData; installPath: string | null }) {
  return (
    <Card className="overflow-hidden">
      <CardHeader className="flex-row items-center justify-between gap-2">
        <div className="space-y-1.5">
          <CardTitle>{app.name}</CardTitle>
          <CardDescription>App ID {app.app_id}</CardDescription>
        </div>
        {app.html_url && (
          <a href={app.html_url} target="_blank" rel="noopener noreferrer">
            <Button variant="outline" size="sm">
              <ExternalLinkIcon />
              Open on GitHub
            </Button>
          </a>
        )}
      </CardHeader>
      <CardContent className="bg-background">
        <CardRow>
          <span>Install on a GitHub organization</span>
          {installPath && (
            <a href={route('github-app.install')}>
              <Button>
                <GithubIcon />
                Install
              </Button>
            </a>
          )}
        </CardRow>
        <Separator />
        <CardRow>
          <span>Sync installations from GitHub</span>
          <SyncButton />
        </CardRow>
        <Separator />
        <CardRow>
          <span className="text-destructive">Remove GitHub App from this instance</span>
          <RemoveButton />
        </CardRow>
      </CardContent>
    </Card>
  );
}

function SyncButton() {
  const form = useForm();
  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('github-app.sync'), {
      preserveScroll: true,
    });
  };
  return (
    <Button type="button" variant="outline" disabled={form.processing} onClick={submit}>
      {form.processing ? <LoaderCircleIcon className="animate-spin" /> : <RefreshCcwIcon />}
      Sync now
    </Button>
  );
}

function RemoveButton() {
  const [open, setOpen] = useState(false);
  const submit = () => {
    router.delete(route('github-app.destroy'), {
      onSuccess: () => setOpen(false),
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button variant="destructive">
          <Trash2Icon />
          Remove
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Remove GitHub App</DialogTitle>
          <DialogDescription>
            This clears the local app config. You should also delete the app on GitHub if you don&apos;t plan to reuse it. Installations with attached
            sites cannot be removed.
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button variant="destructive" onClick={submit}>
            Remove
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
