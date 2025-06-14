import { Head, usePage } from '@inertiajs/react';
import { Server } from '@/types/server';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import ServerLayout from '@/layouts/server/layout';
import { BookOpenIcon, MoreVerticalIcon } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import React from 'react';
import { Site, SiteFeature } from '@/types/site';
import { Separator } from '@/components/ui/separator';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import FeatureAction from '@/pages/site-features/components/feature-action';

export default function SiteFeatures() {
  const page = usePage<{
    server: Server;
    site: Site;
    features: {
      [key: string]: SiteFeature;
    };
  }>();

  return (
    <ServerLayout>
      <Head title={`Features - ${page.props.site.domain}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Features" description="Your site has some features enabled by Vito or other plugins" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/sites/features" target="_blank">
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
              <CardTitle>Site features</CardTitle>
              <CardDescription>Here you can see the list of features and their actions</CardDescription>
            </div>
          </CardHeader>
          <CardContent>
            {Object.entries(page.props.features).map(([key, feature], index) => (
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
                        <FeatureAction key={`action-${actionKey}`} site={page.props.site} featureId={key} actionId={actionKey} action={action}>
                          <DropdownMenuItem onSelect={(e) => e.preventDefault()} disabled={!action.active}>
                            {action.label}
                          </DropdownMenuItem>
                        </FeatureAction>
                      ))}
                    </DropdownMenuContent>
                  </DropdownMenu>
                </div>
                {index < Object.keys(page.props.features).length - 1 && <Separator />}
              </div>
            ))}
          </CardContent>
        </Card>
      </Container>
    </ServerLayout>
  );
}
