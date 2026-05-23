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

export default function StartCommand({ site, children }: { site: Site; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const form = useForm<{ start_command: string }>({
    start_command: site.start_command ?? '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.patch(route('site-settings.update-start-command', { server: site.server_id, site: site.id }), {
      onSuccess: () => setOpen(false),
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Update Start Command</DialogTitle>
          <DialogDescription>The shell command supervisor uses to start your application worker.</DialogDescription>
        </DialogHeader>

        <Form id="start-command-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="start_command">Start Command</Label>
              <Input
                id="start_command"
                type="text"
                value={form.data.start_command}
                placeholder="e.g., npm start"
                onChange={(e) => form.setData('start_command', e.target.value)}
              />
              <p className="text-muted-foreground text-xs">
                The change applies on the next worker restart or deploy — the worker won't restart automatically when you save.
              </p>
              <InputError message={form.errors.start_command} />
            </FormField>
          </FormFields>
        </Form>

        <DialogFooter className="gap-2">
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>
          <Button form="start-command-form" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="size-4 animate-spin" />}
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
