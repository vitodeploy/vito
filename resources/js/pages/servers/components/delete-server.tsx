import { Server } from '@/types/server';
import { FormEvent, ReactNode } from 'react';
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
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { Form, FormField, FormFields } from '@/components/ui/form';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import InputError from '@/components/ui/input-error';
import { LoaderCircleIcon } from 'lucide-react';

export default function DeleteServer({ server, children }: { server: Server; children: ReactNode }) {
  const form = useForm({
    name: '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.delete(route('servers.destroy', server.id));
  };

  return (
    <Dialog>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Delete {server.name}</DialogTitle>
          <DialogDescription className="sr-only">Delete server and its resources.</DialogDescription>
        </DialogHeader>

        <p className="p-4">
          Are you sure you want to delete this server: <strong>{server.name}</strong>? All resources associated with this server will be deleted and
          this action cannot be undone.
        </p>

        <Form id="delete-server-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="server-name">Server name</Label>
              <Input id="server-name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
              <InputError message={form.errors.name} />
            </FormField>
          </FormFields>
        </Form>

        <DialogFooter className="gap-2">
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>

          <Button form="delete-server-form" variant="destructive" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="size-4 animate-spin" />}
            Delete server
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
