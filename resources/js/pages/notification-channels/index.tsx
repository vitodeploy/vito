import SettingsLayout from '@/layouts/settings/layout';
import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import ConnectNotificationChannel from '@/pages/notification-channels/components/connect-notification-channel';
import { VitoTable } from '@/components/vito-table';
import { NotificationChannel } from '@/types/notification-channel';
import { Configs } from '@/types';
import { BookOpenIcon, MoreVerticalIcon } from 'lucide-react';
import { DropdownMenu, DropdownMenuContent, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import Edit from '@/pages/notification-channels/components/edit';
import Delete from '@/pages/notification-channels/components/delete';
import type { InertiaTableData, Row } from 'inertia-table-react';
import { asRow } from '@/lib/inertia-table';

type Page = {
  notificationChannels: InertiaTableData;
  configs: Configs;
};

export default function NotificationChannels() {
  const page = usePage<Page>();

  return (
    <SettingsLayout>
      <Head title="Notification Channels" />
      <Container className="max-w-5xl">
        <div className="flex items-start justify-between">
          <Heading title="Notification Channels" description="Here you can manage all of the notification channel connections" />
          <div className="flex items-center gap-2">
            <a href="https://vitodeploy.com/docs/settings/notification-channels" target="_blank">
              <Button variant="outline">
                <BookOpenIcon />
                <span className="hidden lg:block">Docs</span>
              </Button>
            </a>
            <ConnectNotificationChannel>
              <Button>Connect</Button>
            </ConnectNotificationChannel>
          </div>
        </div>

        <VitoTable
          tableData={page.props.notificationChannels}
          actions={(row: Row) => {
            const notificationChannel = asRow<NotificationChannel>(row, ['id', 'name', 'global']);
            return (
              <div className="flex items-center justify-end">
                <DropdownMenu modal={false}>
                  <DropdownMenuTrigger asChild>
                    <Button variant="ghost" className="h-8 w-8 p-0">
                      <span className="sr-only">Open menu</span>
                      <MoreVerticalIcon />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end">
                    <Edit notificationChannel={notificationChannel} />
                    <DropdownMenuSeparator />
                    <Delete notificationChannel={notificationChannel} />
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            );
          }}
        />
      </Container>
    </SettingsLayout>
  );
}
