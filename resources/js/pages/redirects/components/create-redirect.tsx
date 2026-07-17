import React, { FormEvent, ReactNode, useState } from 'react';
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
import { Form } from '@/components/ui/form';
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { LoaderCircle } from 'lucide-react';
import { Site } from '@/types/site';
import RedirectFormFields, { RedirectForm } from './redirect-form-fields';

export default function CreateRedirect({ site, children }: { site: Site; children: ReactNode }) {
  const [open, setOpen] = useState(false);

  const form = useForm<RedirectForm>({
    mode: '',
    from: '',
    to: '',
    websocket: false,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.post(route('redirects.store', { server: site.server_id, site: site.id }), {
      onSuccess: () => {
        form.reset();
        setOpen(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>Create Redirect</DialogTitle>
          <DialogDescription className="sr-only">Create new Redirect</DialogDescription>
        </DialogHeader>
        <Form className="p-4" id="create-redirect-form" onSubmit={submit}>
          <RedirectFormFields form={form} />
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button type="button" variant="outline">
              Cancel
            </Button>
          </DialogClose>
          <Button form="create-redirect-form" type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircle className="animate-spin" />}
            Create
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
