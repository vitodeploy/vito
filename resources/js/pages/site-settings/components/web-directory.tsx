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

export default function WebDirectory({ site, children }: { site: Site; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const form = useForm<{
    web_directory: string;
  }>({
    web_directory: site.web_directory || '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.patch(route('site-settings.update-web-directory', { server: site.server_id, site: site.id }), {
      onSuccess: () => {
        setOpen(false);
      },
    });
  };

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>{children}</DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Update Web Directory</DialogTitle>
          <DialogDescription>The relative path of your website from {site.path}/</DialogDescription>
        </DialogHeader>

        <Form id="web-directory-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="web_directory">Web Directory</Label>
              <Input
                id="web_directory"
                type="text"
                value={form.data.web_directory}
                placeholder="e.g., public, www, dist (leave empty for root)"
                onChange={(e) => form.setData('web_directory', e.target.value)}
              />
              <InputError message={form.errors.web_directory} />
            </FormField>
          </FormFields>
        </Form>

        <DialogFooter className="gap-2">
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>

          <Button form="web-directory-form" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="size-4 animate-spin" />}
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
