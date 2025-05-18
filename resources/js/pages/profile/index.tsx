import { Head } from '@inertiajs/react';
import SettingsLayout from '@/layouts/settings/layout';
import Container from '@/components/container';
import UpdatePassword from '@/pages/profile/components/update-password';
import UpdateProfile from '@/pages/profile/components/update-profile';
import Heading from '@/components/heading';

export default function Profile() {
  return (
    <SettingsLayout>
      <Head title="Profile settings" />
      <Container className="max-w-5xl">
        <Heading title="Profile settings" description="Manage your profile settings." />
        <UpdateProfile />
        <UpdatePassword />
      </Container>
    </SettingsLayout>
  );
}
