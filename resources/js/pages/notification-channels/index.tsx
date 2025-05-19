import SettingsLayout from '@/layouts/settings/layout';
import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import React from 'react';
import ConnectNotificationChannel from '@/pages/notification-channels/components/connect-notification-channel';
import { DataTable } from '@/components/data-table';
import { columns } from '@/pages/notification-channels/components/columns';
import { NotificationChannel } from '@/types/notification-channel';
import { Configs } from '@/types';

type Page = {
  notificationChannels: {
    data: NotificationChannel[];
  };
  configs: Configs;
};

export default function NotificationChannels() {
  const page = usePage<Page>();

  return (
    <SettingsLayout>
      <Head title="Notification Channels" />
      <Container className="max-w-5xl">
        <div className="flex items-start justify-between">
          <Heading title="Notification Channels" description="Here you can manage all of the notification channel connectinos" />
          <div className="flex items-center gap-2">
            <ConnectNotificationChannel providers={page.props.configs.notification_channels_providers}>
              <Button>Connect</Button>
            </ConnectNotificationChannel>
          </div>
        </div>

        <DataTable columns={columns} data={page.props.notificationChannels.data} />
      </Container>
    </SettingsLayout>
  );
}
