import SettingsLayout from '@/layouts/settings/layout';
import { Head, usePage } from '@inertiajs/react';
import Container from '@/components/container';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import ConnectNotificationChannel from '@/pages/notification-channels/components/connect-notification-channel';
import { DynamicTable } from '@/components/dynamic-table';
import { DynamicTableData, Row } from '@/types/dynamic-table';
import { NotificationChannel } from '@/types/notification-channel';
import { BookOpenIcon, LoaderCircleIcon, MoreVerticalIcon } from 'lucide-react';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog';
import { useForm } from '@inertiajs/react';
import FormSuccessful from '@/components/form-successful';
import { FormEvent, useState } from 'react';
import InputError from '@/components/ui/input-error';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';

function Edit({ notificationChannel }: { notificationChannel: NotificationChannel }) {
  const [open, setOpen] = useState(false);
  const form = useForm({
    name: notificationChannel.name,
    global: notificationChannel.global,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.patch(route('notification-channels.update', notificationChannel.id));
  };
  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem onSelect={(e) => e.preventDefault()}>Edit</DropdownMenuItem>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Edit {notificationChannel.name}</DialogTitle>
          <DialogDescription className="sr-only">Edit notification channel</DialogDescription>
        </DialogHeader>
        <Form id="edit-notification-channel-form" className="p-4" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="name">Name</Label>
              <Input type="text" id="name" name="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
              <InputError message={form.errors.name} />
            </FormField>
            <FormField>
              <div className="flex items-center space-x-3">
                <Checkbox id="global" name="global" checked={form.data.global} onClick={() => form.setData('global', !form.data.global)} />
                <Label htmlFor="global">Is global (accessible in all projects)</Label>
              </div>
              <InputError message={form.errors.global} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button form="edit-notification-channel-form" disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

function Delete({ notificationChannel }: { notificationChannel: NotificationChannel }) {
  const [open, setOpen] = useState(false);
  const form = useForm();

  const submit = () => {
    form.delete(route('notification-channels.destroy', notificationChannel.id), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };
  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <DropdownMenuItem variant="destructive" onSelect={(e) => e.preventDefault()}>
          Delete
        </DropdownMenuItem>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete {notificationChannel.name}</DialogTitle>
          <DialogDescription className="sr-only">Delete notification channel</DialogDescription>
        </DialogHeader>
        <div className="space-y-2 p-4">
          <p>
            Are you sure you want to delete <strong>{notificationChannel.name}</strong>?
          </p>
          <InputError message={form.errors.provider} />
        </div>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button variant="destructive" disabled={form.processing} onClick={submit}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Delete
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}

type Page = {
  notificationChannels: DynamicTableData;
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

        <DynamicTable
          tableData={page.props.notificationChannels}
          actions={(row: Row) => (
            <div className="flex items-center justify-end">
              <DropdownMenu modal={false}>
                <DropdownMenuTrigger asChild>
                  <Button variant="ghost" className="h-8 w-8 p-0">
                    <span className="sr-only">Open menu</span>
                    <MoreVerticalIcon />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <Edit notificationChannel={row as unknown as NotificationChannel} />
                  <DropdownMenuSeparator />
                  <Delete notificationChannel={row as unknown as NotificationChannel} />
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          )}
        />
      </Container>
    </SettingsLayout>
  );
}
