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
import { TagsInput } from '@/components/ui/tags-input';

export default function Aliases({ site, children }: { site: Site; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const form = useForm<{
    aliases: string[];
  }>({
    aliases: site.aliases || [],
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.patch(route('site-settings.update-aliases', { server: site.server_id, site: site.id }), {
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
          <DialogTitle>Update Aliases</DialogTitle>
          <DialogDescription className="sr-only">Update site aliases</DialogDescription>
        </DialogHeader>

        <Form id="aliases-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="aliases">Aliases</Label>
              <TagsInput
                id="aliases"
                type="text"
                value={form.data.aliases}
                placeholder="Add aliases"
                onValueChange={(value) => form.setData('aliases', value)}
              />
              <InputError message={form.errors.aliases} />
              {Object.keys(form.errors)
                .filter((key) => key.startsWith('aliases.'))
                .map((key) => (
                  <InputError key={key} message={form.errors[key as keyof typeof form.errors] as string} />
                ))}
            </FormField>
          </FormFields>
        </Form>

        <DialogFooter className="gap-2">
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>

          <Button form="aliases-form" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="size-4 animate-spin" />}
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
