import { Head, Link, usePage } from '@inertiajs/react';
import { Server, ServerFeature } from '@/types/server';
import Container from '@/components/container';
import HeaderContainer from '@/components/header-container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import ServerLayout from '@/layouts/server/layout';
import { MoreVerticalIcon } from 'lucide-react';
import { Card, CardContent, CardDescription, CardHeader, CardRow, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import FeatureAction from '@/pages/server-features/components/feature-action';

export default function ServerFeatures() {
  const page = usePage<{
    server: Server;
    features: {
      [key: string]: ServerFeature;
    };
  }>();

  return (
    <ServerLayout>
      <Head title={`Features - ${page.props.server.name}`} />

      <Container className="max-w-5xl">
        <HeaderContainer>
          <Heading title="Features" description="Your server has some features enabled by Vito or other plugins" />
        </HeaderContainer>

        <Card>
          <CardHeader className="flex-row items-center justify-between gap-2">
            <div className="space-y-2">
              <CardTitle>Server features</CardTitle>
              <CardDescription>Here you can see the list of features and their actions</CardDescription>
            </div>
          </CardHeader>
          <CardContent>
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
                          <FeatureAction key={`action-${actionKey}`} server={page.props.server} featureId={key} actionId={actionKey} action={action}>
                            <DropdownMenuItem onSelect={(e) => e.preventDefault()} disabled={!action.active}>
                              {action.label}
                            </DropdownMenuItem>
                          </FeatureAction>
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
