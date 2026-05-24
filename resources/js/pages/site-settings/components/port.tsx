import { FormEvent, ReactNode, useState } from 'react';
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
import InputError from '@/components/ui/input-error';
import { LoaderCircleIcon } from 'lucide-react';
import { Site } from '@/types/site';
import { Input } from '@/components/ui/input';

export default function Port({ site, children }: { site: Site; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const form = useForm<{ port: string }>({
    port: site.port?.toString() ?? '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.patch(route('site-settings.update-port', { server: site.server_id, site: site.id }), {
      onSuccess: () => setOpen(false),
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Update Port</DialogTitle>
          <DialogDescription>The port your application listens on. The VHost will be regenerated when you save.</DialogDescription>
        </DialogHeader>

        <Form id="port-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="port">Port</Label>
              <Input
                id="port"
                type="number"
                min={1024}
                max={65535}
                value={form.data.port}
                placeholder="3000"
                onChange={(e) => form.setData('port', e.target.value)}
              />
              <p className="text-muted-foreground text-xs">
                Make sure your application is configured to listen on this port (e.g., via the <code>PORT</code> environment variable). Use a
                non-privileged port (1024–65535).
              </p>
              <InputError message={form.errors.port} />
            </FormField>
          </FormFields>
        </Form>

        <DialogFooter className="gap-2">
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button form="port-form" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="size-4 animate-spin" />}
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
