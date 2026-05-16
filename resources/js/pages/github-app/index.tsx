import AdminLayout from '@/layouts/admin/layout';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardRow } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Form, FormField, FormFields } from '@/components/ui/form';
import InputError from '@/components/ui/input-error';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
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
};

type PageProps = {
  githubApp: GithubAppData | null;
  manifest: Manifest;
  manualSetup: ManualSetup;
  installPath: string | null;
  installations: { data: SourceControl[] };
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
              Webhook events from GitHub cannot reach this instance. Installs and uninstalls will be detected on the 4-hour sync (or by clicking
              the Sync button below).
            </AlertDescription>
          </Alert>
        )}

        {!githubApp ? <CreateAppCard manifest={manifest} manualSetup={manualSetup} /> : <ConfiguredCard app={githubApp} installPath={installPath} />}

        {githubApp && (
          <Card>
            <CardContent>
              <div className="p-4">
                <h3 className="text-sm font-medium">Connected installations</h3>
                <p className="text-muted-foreground text-sm">Organizations that have installed this GitHub App.</p>
              </div>
              {installations.data.length === 0 ? (
                <div className="text-muted-foreground p-4 text-sm">No installations yet. Install the app on a GitHub organization to begin.</div>
              ) : (
                installations.data.map((sc) => (
                  <CardRow key={sc.id}>
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
                We&apos;ll send a pre-filled manifest to GitHub. GitHub creates the app, generates credentials, and redirects you back here.
                Requires a publicly reachable hostname (not <code>.test</code> / <code>.localhost</code>).
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

function ManualSetupForm({ manualSetup }: { manualSetup: ManualSetup }) {
  const form = useForm({
    app_id: '',
    app_slug: '',
    name: '',
    client_id: '',
    client_secret: '',
    webhook_secret: '',
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
    <div className="space-y-4">
      <Alert>
        <AlertTitle>Step 1 — create the app on GitHub</AlertTitle>
        <AlertDescription>
          <div className="space-y-2">
            <p>
              Open{' '}
              <a href={manualSetup.create_url} target="_blank" rel="noopener noreferrer" className="underline">
                github.com/settings/apps/new
              </a>{' '}
              and fill in these fields:
            </p>
            <ul className="ml-5 list-disc space-y-1 text-sm">
              <li><strong>GitHub App name:</strong> anything (e.g. Vito on your-host)</li>
              <li><strong>Homepage URL:</strong> <code>{manualSetup.homepage_url}</code></li>
              <li><strong>Callback URL:</strong> <code>{manualSetup.callback_url}</code></li>
              <li><strong>Setup URL (post installation):</strong> <code>{manualSetup.setup_url}</code> — tick "Redirect on update"</li>
              <li><strong>Webhook URL:</strong> <code>{manualSetup.webhook_url}</code> (leave Active checked)</li>
              <li><strong>Webhook secret:</strong> generate a random string and copy it for Step 2</li>
              <li><strong>Permissions → Repository → Contents:</strong> Read-only</li>
              <li><strong>Permissions → Repository → Metadata:</strong> Read-only (auto-selected)</li>
              <li><strong>Subscribe to events:</strong> Push</li>
              <li><strong>Where can this GitHub App be installed?:</strong> "Only on this account"</li>
            </ul>
            <p>After creating, on the app page generate a <strong>Client secret</strong>, then a <strong>Private key</strong> (downloads as a .pem file). Note the <strong>App ID</strong> from the top of the page.</p>
          </div>
        </AlertDescription>
      </Alert>

      <Form id="github-app-manual" onSubmit={submit}>
        <FormFields>
          <div className="grid grid-cols-2 items-start gap-4">
            <FormField>
              <Label htmlFor="app_id">App ID</Label>
              <Input id="app_id" type="text" inputMode="numeric" value={form.data.app_id} onChange={(e) => form.setData('app_id', e.target.value)} />
              <InputError message={form.errors.app_id} />
            </FormField>
            <FormField>
              <Label htmlFor="app_slug">App slug</Label>
              <Input
                id="app_slug"
                type="text"
                placeholder="vito-on-your-host"
                value={form.data.app_slug}
                onChange={(e) => form.setData('app_slug', e.target.value)}
              />
              <p className="text-muted-foreground text-xs">Lowercase, in the app's public URL (github.com/apps/&lt;slug&gt;).</p>
              <InputError message={form.errors.app_slug} />
            </FormField>
          </div>
          <FormField>
            <Label htmlFor="name">Display name (optional)</Label>
            <Input id="name" type="text" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
            <InputError message={form.errors.name} />
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
          <FormField>
            <Label htmlFor="html_url">App page URL (optional)</Label>
            <Input
              id="html_url"
              type="text"
              placeholder="https://github.com/apps/your-slug"
              value={form.data.html_url}
              onChange={(e) => form.setData('html_url', e.target.value)}
            />
            <InputError message={form.errors.html_url} />
          </FormField>
          <Button type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Save GitHub App
          </Button>
        </FormFields>
      </Form>
    </div>
  );
}

function ConfiguredCard({ app, installPath }: { app: GithubAppData; installPath: string | null }) {
  return (
    <Card>
      <CardContent>
        <CardRow>
          <div>
            <div className="font-medium">{app.name}</div>
            <div className="text-muted-foreground text-xs">App ID {app.app_id}</div>
          </div>
          <div className="flex items-center gap-2">
            {app.html_url && (
              <a href={app.html_url} target="_blank" rel="noopener noreferrer">
                <Button variant="outline" size="sm">
                  <ExternalLinkIcon />
                  Open on GitHub
                </Button>
              </a>
            )}
          </div>
        </CardRow>
        <Separator />
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
            This clears the local app config. You should also delete the app on GitHub if you don&apos;t plan to reuse it. Installations with
            attached sites cannot be removed.
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
