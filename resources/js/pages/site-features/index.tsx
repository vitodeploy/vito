import { Head, Link, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import ServerLayout from '@/layouts/server/layout';
import SiteBanners from '@/components/site-banners';
import { MoreVerticalIcon, TriangleAlertIcon } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardRow, CardTitle } from '@/components/ui/card';
import { Site, SiteFeature } from '@/types/site';
import { Separator } from '@/components/ui/separator';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { useDialog } from '@/hooks/use-dialog';

export default function SiteFeatures() {
  const page = usePage<{
    server: Server;
    site: Site;
    features: {
      [key: string]: SiteFeature;
    };
  }>();
  const dialog = useDialog();

  return (
    <ServerLayout>
      <Head title={`Features - ${page.props.site.domain}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Features" description="Your site has some features enabled by Vito or other plugins" />
        </HeaderContainer>

        <SiteBanners site={page.props.site} />

        <Alert>
          <TriangleAlertIcon className="text-warning!" />
          <AlertDescription className="flex gap-1">
            Vito now uses the new plugins system. If the feature you're looking for is not here, check the
            <Link className="text-primary" href={route('plugins')}>
              plugins
            </Link>
            and install the required one.
          </AlertDescription>
        </Alert>

        <Card className="overflow-hidden">
          <CardHeader className="flex-row items-center justify-between gap-2">
            <div className="space-y-2">
              <CardTitle>Site features</CardTitle>
              <CardDescription>Here you can see the list of features and their actions</CardDescription>
            </div>
          </CardHeader>
          <CardContent className="bg-background">
            {Object.entries(page.props.features).length > 0 ? (
              Object.entries(page.props.features).map(([key, feature], index) => (
                <div key={`feature-${key}`}>
                  <div className="flex items-center justify-between p-4">
                    <div className="space-y-1">
                      <p>{feature.label}</p>
                      <p className="text-muted-foreground text-sm">{feature.description}</p>
                    </div>
                    <DropdownMenu modal={false}>
                      <DropdownMenuTrigger asChild>
                        <Button variant="outline">
                          Actions
                          <MoreVerticalIcon />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        {Object.entries(feature.actions || {}).map(([actionKey, action]) => (
                          <DropdownMenuItem
                            key={`action-${actionKey}`}
                            disabled={!action.active}
                            onSelect={() => dialog.siteFeatureAction.open({ site: page.props.site, featureId: key, actionId: actionKey, action })}
                          >
                            {action.label}
                          </DropdownMenuItem>
                        ))}
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </div>
                  {index < Object.entries(page.props.features).length - 1 && <Separator />}
                </div>
              ))
            ) : (
              <CardRow className="flex-col items-center justify-center space-y-2">
                <span className="text-muted-foreground">No available features</span>
                <Link href={route('plugins')} prefetch>
                  <Button variant="outline">Explore Plugins</Button>
                </Link>
              </CardRow>
            )}
          </CardContent>
        </Card>
      </Container>
    </ServerLayout>
  );
}
