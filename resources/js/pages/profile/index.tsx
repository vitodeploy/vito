import { Head } from '@inertiajs/react';
import DeleteUser from '@/pages/profile/components/delete-user';
import SettingsLayout from '@/layouts/settings/layout';
import Container from '@/components/container';
import UpdatePassword from '@/pages/profile/components/update-password';
import UpdateUser from '@/pages/profile/components/update-user';
import Heading from '@/components/heading';

export default function Profile({ mustVerifyEmail, status }: { mustVerifyEmail: boolean; status?: string }) {
  return (
    <SettingsLayout>
      <Head title="Profile settings" />
      <Container className="max-w-xl">
        <Heading title="Profile settings" description="Manage your profile settings." />
        <UpdateUser mustVerifyEmail={mustVerifyEmail} status={status} />
        <UpdatePassword />
        <DeleteUser />
      </Container>
    </SettingsLayout>
  );
}
