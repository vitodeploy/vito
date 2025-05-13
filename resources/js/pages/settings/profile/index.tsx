import { Head } from '@inertiajs/react';
import DeleteUser from '@/pages/settings/profile/partials/delete-user';
import SettingsLayout from '@/layouts/settings/layout';
import Container from '@/components/container';
import UpdatePassword from '@/pages/settings/profile/partials/update-password';
import UpdateUser from '@/pages/settings/profile/partials/update-user';

export default function Profile({ mustVerifyEmail, status }: { mustVerifyEmail: boolean; status?: string }) {
  return (
    <SettingsLayout>
      <Head title="Profile settings" />
      <Container className="max-w-xl">
        <UpdateUser mustVerifyEmail={mustVerifyEmail} status={status} />
        <UpdatePassword />
        <DeleteUser />
      </Container>
    </SettingsLayout>
  );
}
