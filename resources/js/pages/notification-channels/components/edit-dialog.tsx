import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import FormSuccessful from '@/components/form-successful';
import { FormEvent } from 'react';
import InputError from '@/components/ui/input-error';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';
import { NotificationChannel } from '@/types/notification-channel';

export default function NotificationChannelEditDialog({
  open,
  onOpenChange,
  notificationChannel,
}: {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  notificationChannel: NotificationChannel;
}) {
  const form = useForm({
    name: notificationChannel.name,
    global: notificationChannel.global,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.patch(route('notification-channels.update', notificationChannel.id), {
      onSuccess: () => onOpenChange(false),
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Edit {notificationChannel.name}</DialogTitle>
          <DialogDescription className="sr-only">Edit notification channel</DialogDescription>
        </DialogHeader>
        <Form id="edit-notification-channel" className="p-4" onSubmit={submit}>
          <FormFields>
            <FormField>
              <Label htmlFor="name">Name</Label>
              <Input type="text" id="name" name="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
              <InputError message={form.errors.name} />
            </FormField>
            <FormField>
              <div className="flex items-center space-x-3">
                <Checkbox
                  id="global"
                  name="global"
                  checked={form.data.global}
                  onCheckedChange={(checked) => form.setData('global', Boolean(checked))}
                />
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
          <Button form="edit-notification-channel" type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            <FormSuccessful successful={form.recentlySuccessful} />
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
