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
import SourceControlSelect from '@/pages/source-controls/components/source-control-select';

export default function ChangeSourceControl({ site, children }: { site: Site; children: ReactNode }) {
  const [open, setOpen] = useState(false);
  const form = useForm<{
    source_control: string;
  }>({
    source_control: site.source_control_id.toString() || '',
  });

  const submit = (e: FormEvent) => {
    e.preventDefault();
    form.patch(`/servers/${site.server_id}/sites/${site.id}/settings/source-control`, {
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
          <DialogTitle>Change source control</DialogTitle>
          <DialogDescription className="sr-only">Change site's source control.</DialogDescription>
        </DialogHeader>

        <Form id="change-php-version-form" onSubmit={submit} className="p-4">
          <FormFields>
            <FormField>
              <Label htmlFor="versino">PHP version</Label>
              <SourceControlSelect value={form.data.source_control} onValueChange={(value) => form.setData('source_control', value)} />
              <InputError message={form.errors.source_control} />
            </FormField>
          </FormFields>
        </Form>

        <DialogFooter className="gap-2">
          <DialogClose asChild>
            <Button variant="outline">Cancel</Button>
          </DialogClose>

          <Button form="change-php-version-form" disabled={form.processing}>
            {form.processing && <LoaderCircleIcon className="size-4 animate-spin" />}
            Save
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
