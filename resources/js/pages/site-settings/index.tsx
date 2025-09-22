import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import ServerLayout from '@/layouts/server/layout';
import { BookOpenIcon } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import DateTime from '@/components/date-time';
import React from 'react';
import { Site } from '@/types/site';
import { Badge } from '@/components/ui/badge';
import ChangeBranch from '@/pages/site-settings/components/branch';
import { SourceControl } from '@/types/source-control';
import CopyableBadge from '@/components/copyable-badge';
import ChangePHPVersion from '@/pages/site-settings/components/php-version';
import DeleteSite from '@/pages/site-settings/components/delete-site';
import VHost from '@/pages/site-settings/components/vhost';
import ChangeSourceControl from '@/pages/site-settings/components/source-control';
import Aliases from './components/aliases';
import WebDirectory from './components/web-directory';

export default function Databases() {
  const page = usePage<{
    server: Server;
    site: Site;
    sourceControl?: SourceControl;
  }>();

  return (
    <ServerLayout>
      <Head title={`Settings - ${page.props.site.domain}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Settings" description="Here you can manage your site's settings" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/sites/settings" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
          </div>
        </HeaderContainer>

        <Card>
          <CardHeader className="flex-row items-center justify-between gap-2">
            <div className="space-y-2">
              <CardTitle>Site details</CardTitle>
              <CardDescription>Update site details</CardDescription>
            </div>
          </CardHeader>
          <CardContent>
            <div className="flex items-center justify-between p-4">
              <span>ID</span>
              <span className="text-muted-foreground">{page.props.site.id}</span>
            </div>
            <Separator />
            <div className="flex items-center justify-between p-4">
              <span>Domain</span>
              <a href={page.props.site.url} target="_blank" className="text-muted-foreground hover:underline">
                {page.props.site.domain}
              </a>
            </div>
            <Separator />
            <div className="flex items-center justify-between p-4">
              <span>Aliases</span>
              <Aliases site={page.props.site}>
                <Button variant="outline" className="h-6">
                  Update Aliases
                </Button>
              </Aliases>
            </div>
            <Separator />
            <div className="flex items-center justify-between p-4">
              <span>Type</span>
              <span className="text-muted-foreground">{page.props.site.type}</span>
            </div>
            <Separator />
            <div className="flex items-center justify-between p-4">
              <span>Source control</span>
              {page.props.site.source_control_id ? (
                <ChangeSourceControl site={page.props.site}>
                  <Button variant="outline" className="h-6">
                    {page.props.sourceControl?.provider}
                  </Button>
                </ChangeSourceControl>
              ) : (
                <span className="text-muted-foreground">-</span>
              )}
            </div>
            <Separator />
            <div className="flex items-center justify-between p-4">
              <span>Repository</span>
              <span className="text-muted-foreground">{page.props.site.repository || '-'}</span>
            </div>
            <Separator />
            <div className="flex items-center justify-between p-4">
              <span>Branch</span>
              {page.props.site.source_control_id ? (
                <ChangeBranch site={page.props.site}>
                  <Button variant="outline" className="h-6">
                    {page.props.site.branch}
                  </Button>
                </ChangeBranch>
              ) : (
                '-'
              )}
            </div>
            <Separator />
            <div className="flex items-center justify-between p-4">
              <span>VHost</span>
              <VHost site={page.props.site}>
                <Button variant="outline" className="h-6">
                  Edit VHost
                </Button>
              </VHost>
            </div>
            <Separator />
            <div className="flex items-center justify-between p-4">
              <span>Web directory</span>
              <WebDirectory site={page.props.site}>
                <Button variant="outline" className="h-6">
                  {page.props.site.web_directory || '/'}
                </Button>
              </WebDirectory>
            </div>
            <Separator />
            <div className="flex items-center justify-between p-4">
              <span>Path</span>
              <CopyableBadge text={page.props.site.path} />
            </div>
            <Separator />
            <div className="flex items-center justify-between p-4">
              <span>PHP version</span>
              {page.props.site.php_version ? (
                <ChangePHPVersion site={page.props.site}>
                  <Button variant="outline" className="h-6">
                    {page.props.site.php_version}
                  </Button>
                </ChangePHPVersion>
              ) : (
                <span className="text-muted-foreground">-</span>
              )}
            </div>
            <Separator />
            <div className="flex items-center justify-between p-4">
              <span>Status</span>
              <Badge variant={page.props.site.status_color}>{page.props.site.status}</Badge>
            </div>
            <Separator />
            <div className="flex items-center justify-between p-4">
              <span>Created at</span>
              <span className="text-muted-foreground">
                <DateTime date={page.props.site.created_at} />
              </span>
            </div>
          </CardContent>
        </Card>

        <Card className="border-destructive/50">
          <CardHeader>
            <CardTitle>Delete site</CardTitle>
            <CardDescription>Here you can delete the site.</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-2 p-4">
              <p>please note that this action is irreversible and will delete all data associated with the site.</p>

              <DeleteSite site={page.props.site}>
                <Button variant="destructive">Delete site</Button>
              </DeleteSite>
            </div>
          </CardContent>
        </Card>
      </Container>
    </ServerLayout>
  );
}
