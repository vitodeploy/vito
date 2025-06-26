import { Head } from '@inertiajs/react';
import SettingsLayout from '@/layouts/settings/layout';
import Container from '@/components/container';
import UpdatePassword from '@/pages/profile/components/update-password';
import UpdateProfile from '@/pages/profile/components/update-profile';
import Heading from '@/components/heading';
import TwoFactor from '@/pages/profile/components/two-factor';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { useState } from 'react';

export default function Profile() {
  const [tab, setTab] = useState('info');
  return (
    <SettingsLayout>
      <Head title="Profile settings" />
      <Container className="max-w-5xl">
        <Heading title="Profile settings" description="Manage your profile settings." />

        <Tabs defaultValue={tab} onValueChange={setTab}>
          <TabsList>
            <TabsTrigger value="info">Info</TabsTrigger>
            <TabsTrigger value="password">Password</TabsTrigger>
            <TabsTrigger value="two_factor">Two Factor</TabsTrigger>
          </TabsList>
          <TabsContent value="info">
            <UpdateProfile />
          </TabsContent>
          <TabsContent value="password">
            <UpdatePassword />
          </TabsContent>
          <TabsContent value="two_factor">
            <TwoFactor />
          </TabsContent>
        </Tabs>
      </Container>
    </SettingsLayout>
  );
}
