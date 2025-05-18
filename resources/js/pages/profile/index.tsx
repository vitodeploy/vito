import { Head } from '@inertiajs/react';
import SettingsLayout from '@/layouts/settings/layout';
import Container from '@/components/container';
import UpdatePassword from '@/pages/profile/components/update-password';
import UpdateUser from '@/pages/profile/components/update-user';
import Heading from '@/components/heading';

export default function Profile() {
  return (
    <SettingsLayout>
      <Head title="Profile settings" />
      <Container className="max-w-5xl">
        <Heading title="Profile settings" description="Manage your profile settings." />
        <UpdateUser />
        <UpdatePassword />
      </Container>
    </SettingsLayout>
  );
}
