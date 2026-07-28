import { FormEvent } from 'react';
import { Form } from '@/components/ui/form';
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { LoaderCircle } from 'lucide-react';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Redirect } from '@/types/redirect';
import RedirectFormFields, { RedirectForm } from './redirect-form-fields';

export default function EditRedirect({ open, onOpenChange, redirect }: { open: boolean; onOpenChange: (open: boolean) => void; redirect: Redirect }) {
  const form = useForm<RedirectForm>({
    mode: String(redirect.mode),
    from: redirect.from,
    to: redirect.to,
    websocket: redirect.websocket,
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.put(route('redirects.update', { server: redirect.server_id, site: redirect.site_id, redirect: redirect.id }), {
      onSuccess: () => onOpenChange(false),
    });
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-lg" onCloseAutoFocus={(e) => e.preventDefault()}>
        <DialogHeader>
          <DialogTitle>Edit Redirect</DialogTitle>
          <DialogDescription className="sr-only">Edit Redirect</DialogDescription>
        </DialogHeader>
        <Form className="p-4" id="edit-redirect-form" onSubmit={submit}>
          <RedirectFormFields form={form} />
        </Form>
        <DialogFooter>
          <DialogClose asChild>
            <Button type="button" variant="outline">
              Cancel
            </Button>
          </DialogClose>
          <Button form="edit-redirect-form" type="submit" disabled={form.processing}>
            {form.processing && <LoaderCircle className="animate-spin" />}
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
