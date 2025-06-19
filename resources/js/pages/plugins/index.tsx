import SettingsLayout from '@/layouts/settings/layout';
import { Head, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import React, { useState } from 'react';
import Container from '@/components/container';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import OfficialPlugins from '@/pages/plugins/components/official';
import InstalledPlugins from '@/pages/plugins/components/installed';
import { Plugin } from '@/types/plugin';
import CommunityPlugins from '@/pages/plugins/components/community';
import { Button } from '@/components/ui/button';
import { BookOpenIcon } from 'lucide-react';
import Install from '@/pages/plugins/components/install';

export default function Plugins() {
  const [tab, setTab] = useState('installed');
  const page = usePage<{
    plugins: Plugin[];
  }>();

  return (
    <SettingsLayout>
      <Head title="Plugins" />

      <Container className="max-w-5xl">
        <div className="flex items-start justify-between">
          <Heading title="Plugins" description="Here you can install/uninstall plugins" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/plugins" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <Install />
          </div>
        </div>

        <Tabs defaultValue={tab} onValueChange={setTab}>
          <TabsList>
            <TabsTrigger value="installed">Installed</TabsTrigger>
            <TabsTrigger value="official">Official</TabsTrigger>
            <TabsTrigger value="community">Community</TabsTrigger>
          </TabsList>
          <TabsContent value="installed">
            <Card>
              <CardHeader>
                <CardTitle>Installed plugins</CardTitle>
                <CardDescription>All the installed plugins</CardDescription>
              </CardHeader>
              <CardContent>
                <InstalledPlugins plugins={page.props.plugins} />
              </CardContent>
            </Card>
          </TabsContent>
          <TabsContent value="official">
            <Card>
              <CardHeader>
                <CardTitle>Official plugins</CardTitle>
                <CardDescription>These plugins are developed and maintained by VitoDeploy's team</CardDescription>
              </CardHeader>
              <CardContent>
                <OfficialPlugins />
              </CardContent>
            </Card>
          </TabsContent>
          <TabsContent value="community">
            <Card>
              <CardHeader>
                <CardTitle>Community plugins</CardTitle>
                <CardDescription>These plugins are developed and maintained by the community.</CardDescription>
              </CardHeader>
              <CardContent>
                <CommunityPlugins />
              </CardContent>
            </Card>
          </TabsContent>
        </Tabs>
      </Container>
    </SettingsLayout>
  );
}
