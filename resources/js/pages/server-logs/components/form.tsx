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
import { FormEvent, ReactNode, useState } from 'react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Button } from '@/components/ui/button';
import { useForm, usePage } from '@inertiajs/react';
import { LoaderCircleIcon } from 'lucide-react';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { ServerLog } from '@/types/server-log';
import { Server } from '@/types/server';

export default function LogForm({ serverLog, children }: { serverLog?: ServerLog; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const page = usePage<{ server: Server }>();
  const form = useForm<{
    path: string;
  }>({
    path: serverLog?.name || '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    if (serverLog) {
      form.put(`/servers/${page.props.server.id}/logs/${serverLog.id}`, {
        onSuccess: () => {
          setOpen(false);
          form.reset();
        },
      });
      return;
    }

    form.post(`/servers/${page.props.server.id}/logs`, {
      onSuccess: () => {
        setOpen(false);
        form.reset();
      },
    });
  };
  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>{serverLog ? 'Edit' : 'Create'} remote log</DialogTitle>
          <DialogDescription className="sr-only">{serverLog ? 'Edit' : 'Create new'} remote log</DialogDescription>
        </DialogHeader>
        <Form id="remote-log-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="path">Path</Label>
              <Input type="text" id="path" value={form.data.path} onChange={(e) => form.setData('path', e.target.value)} />
              <InputError message={form.errors.path} />
            </FormField>
          </FormFields>
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button variant="outline">Close</Button>
          </DialogClose>
          <Button form="remote-log-form" type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="animate-spin" />}
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
